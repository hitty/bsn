<?php
  require_once('includes/class.estate.php');
  class TelegramException extends Exception{
      private $exception_description = "";
      public function __construct($message,$code, $exception_description){
          parent::__construct($message,$code,null);
          $this->exception_description = strval($exception_description);
      }
      public function __toString(){
          return __CLASS__.": [".$this->code."]: ".$this->message."\n";
      }
      
      public function getFullMessage(){
          return array('message' => $this->message,"additional_description" => $this->exception_description);
      }
      
      public static function exceptionHandler(TelegramException $exception,$line_number){
          if(empty($exception) || empty($exception->code)) return false;
          require_once('includes/class.email.php');
          $self = get_called_class();
          $exception_info = $exception->getFullMessage();
          switch($exception->code){
              case 1:
                $mailer = new EMailer('mail');
                $mailer->sendEmail("hitty@bsn.ru","Миша",$exception_info['message']." в строке ".$line_number,false,false,false,$exception_info['additional_description']);
              break;
              case 2:
                $mailer = new EMailer('mail');
                $mailer->sendEmail("hitty@bsn.ru","Миша",$exception_info['message']." в строке ".$line_number,false,false,false,$exception_info['additional_description']);
              break;
              case 3:
                $mailer = new EMailer('mail');
                $mailer->sendEmail("hitty@bsn.ru","Миша",$exception_info['message']." в строке ".$line_number,false,false,false,$exception_info['additional_description']);
              break;
          }
          return true;
      }
  }
  class TelegramController{
      //id нашего канала в telegram
      private static $channel_id = "@bsn_ru";
	  //private static $channel_id = -2147483648;
      
      //test
      //set webhook https://api.telegram.org/bot312438821:AAE8H5LMHVho5nthOCxkGgCfgsFiuBV1R7A/setWebhook?url=https://www.bsn.ru/telegramBot/AAE8H5LMHVho5nthOCxkGgCfgsFiuBV1R7A/
      protected static $bot_token;
      protected static $webhook_url;
      protected static $api_url;
      
      //production
      //set webhook https://api.telegram.org/bot619307156:AAGnZD5KFcDo2R4Y_dv3A38yL9hC3aV6Rcg/setWebhook?url=https://www.bsn.ru/telegramBot/AAGnZD5KFcDo2R4Y_dv3A38yL9hC3aV6Rcg/
      //private static $bot_token;//токен бота на продакшне
      //private static $webhook_url;
      //private static $api_url;
      
      //основные методы для работы
      private static function apiRequestWebhook($method, $parameters) {
          if (!is_string($method)) {
              error_log("Method name must be a string\n");
              return false;
          }

          if (!$parameters) {
              $parameters = array();
          } else if (!is_array($parameters)) {
              error_log("Parameters must be an array\n");
              return false;
          }

          $parameters["method"] = $method;

          header("Content-Type: application/json");
          echo json_encode($parameters);
          return true;
      }
      /**
      * исполняем curl-запрос
      * 
      * @param mixed $handle - curl
      */
      private static function exec_curl_request($handle) {
          $response = curl_exec($handle);

          if ($response === false) {
            $errno = curl_errno($handle);
            $error = curl_error($handle);
			echo "Curl returned error $errno: $error\n";
            error_log("Curl returned error $errno: $error\n");
            curl_close($handle);
            return false;
          }

          $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
          curl_close($handle);

          if ($http_code >= 500) {
            sleep(10);
            return false;
          } else if ($http_code != 200) {
            $response = json_decode($response, true);
            error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
            if ($http_code == 401) {
              throw new Exception('Invalid access token provided');
            }
            return false;
          } else {
            $response = json_decode($response, true);
            if (isset($response['description'])) {
              error_log("Request was successfull: {$response['description']}\n");
            }
            $response = $response['result'];
          }

          return $response;
      }
      /**
      * запрос к API через GET
      * 
      * @param mixed $method
      * @param mixed $parameters
      */
      protected static function apiRequestGet($method, $parameters) {
          
          $self = get_called_class();
          file_put_contents("modules/telegramBot/errors.log",date("d.m.Y H:i:s")." ,md:".$method." ".json_encode($parameters)."\r\n\r\n",FILE_APPEND);
          if (!is_string($method)) {
            error_log("Method name must be a string\n");
            return false;
          }

          if (!$parameters) {
            $parameters = array();
          } else if (!is_array($parameters)) {
            error_log("Parameters must be an array\n");
            return false;
          }

          foreach ($parameters as $key => &$val) {
            if (!is_numeric($val) && !is_string($val)) {
              $val = json_encode($val);
            }
          }
          
          //если это отправка сообщения, логируем
          if($method == "sendMessage") self::logMessageTo($parameters);
          
          $url = $self::$api_url.$method.'?'.http_build_query($parameters);
          file_put_contents("modules/telegramBot/errors.log",date("d.m.Y H:i:s")." request: ".$url."\r\n\r\n",FILE_APPEND);
		  echo "request to url: ".$url."\r\n";
          //образец:
          //https://api.telegram.org/bot312438821:AAE8H5LMHVho5nthOCxkGgCfgsFiuBV1R7A/sendMessage?chat_id=-1001099909798&text=123
          $handle = curl_init($url);
          curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
          curl_setopt($handle, CURLOPT_TIMEOUT, 60);

          return self::exec_curl_request($handle);
      }
      /**
      * запрос к API через POST
      * 
      * @param mixed $method - название метода
      * @param mixed $parameters - параметры
      */
      protected static function apiRequestPost($method, $parameters) {
          $self = get_called_class();
          
          if (!is_string($method)) {
            error_log("Method name must be a string\n");
            return false;
          }

          if (empty($parameters)) $parameters = array();
          else if (!is_array($parameters)) return false;

          //если это отправка сообщения, логируем
          if($method == "sendMessage") self::logMessageTo($parameters);
          
          $parameters["method"] = $method;

          $handle = curl_init($self::$api_url);
          curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
          curl_setopt($handle, CURLOPT_TIMEOUT, 60);
          curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
          curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

          return self::exec_curl_request($handle);
      }
      
      //проверяем Webhook, если отсутствует, устанавливаем
      public static function checkWebHook(){
          $result = self::apiRequestPost("getWebhookInfo",array());
          //to set webhook: https://api.telegram.org/bot312438821:AAE8H5LMHVho5nthOCxkGgCfgsFiuBV1R7A/setWebhook?url=https://www.bsn.ru/telegramBot/AAE8H5LMHVho5nthOCxkGgCfgsFiuBV1R7A/
          if(empty($result)) $result = self::apiRequestPost("setWebhook",array("url" => self::$webhook_url));
          
          if(empty($result)) throw new TelegramException("Проблемы с webhook у бота Telegram",2,"Не получилось установить webhook");
          
          return (!empty($result));
      }
      
      //сверяем метод и токен, чтобы знать что запрос идет из телеграма
      public static function checkInputRequest(){
          $self = get_called_class();
          $token_part = explode(':',$self::$bot_token);
          $token_part = array_pop($token_part);
          if(!($_SERVER['REQUEST_METHOD'] == 'POST' && $_SERVER['REQUEST_URI'] == "/telegramBot/".$token_part."/")){
              throw new TelegramException("Неверный входящий запрос",1,"method = ".$_SERVER['REQUEST_METHOD']."\r\nuri = ".$_SERVER['REQUEST_URI']."\r\nexpected_token_part = ".$token_part."");
          }else file_put_contents("modules/telegrambot/errors.log","REQUEST: ".$_SERVER['REQUEST_URI']."\r\n",FILE_APPEND);
          return true;
      }
      
      /**
      * прием и обработка запроса
      * 
      */
      public static function acceptRequest(){
          $self = get_called_class();
          //проверяем что входящий запрос от телеграма
          //if(!$self::checkInputRequest()){
          //if(false){
          $self::checkInputRequest();
          
          $input = file_get_contents("php://input");
          $update = json_decode($input, true);
          //$update = json_decode('{"update_id":86617763,"message":{"message_id":3879,"from":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430"},"chat":{"id":254877490,"first_name":"\u041c\u0438\u0448\u0430","type":"private"},"date":1485250970,"text":"\u043a\u043e\u043c\u043d\u0430\u0442\u0430"}}',true);
          
          file_put_contents("modules/telegramBot/errors.log",date("d.m.Y H:i:s")."\r\nupdate: ".json_encode($update)."\r\n",FILE_APPEND);
          if (!$update || (empty($update['message']) && empty($update['channel_post']))){
              throw new TelegramException("входящий запрос пуст",3,"json_input = ".json_encode($update));
              return false;
          } 
          $self::acceptMessage($update["message"]);
          return true;
      }
      
      //логирование диалога с пользователем
      /**
      * логируем отправку сообщения пользователю
      * 
      * @param mixed $parameters array('chat_id'=>?,'text'=>?)
      */
      private static function logMessageTo($parameters){
          
          if(empty($parameters['chat_id'])) return false;
          
          global $db;
          global $sys_tables;
          if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
          $db->insertFromArray($sys_tables['telegram_dialogs'],
                               array('id_chat'  => $parameters['chat_id'],
                                     'message'  => $parameters['text'],
                                     'to_from' => 1,
                                     )
                               );
      }
      /**
      * логируем прием сообщения от пользователя
      * 
      * @param mixed $input array('id_chat'=>?,'message'=>?)
      */
      private static function logMessageFrom($input){
          
          if(empty($input['chat']['id']) || empty($input['text'])) return false;
          
          global $db;
          global $sys_tables;
          if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
          
          $db->insertFromArray($sys_tables['telegram_dialogs'],
                               array('id_chat'  => $input['chat']['id'],
                                     'message'  => $input['text'],
                                     'to_from' => 2,
                                     )
                               );
          return $db->insert_id;
      }
      
      /**
      * добавляем пользователя в наш список контактов
      * 
      * @param mixed $input - php://input
      */
      public static function addUserToContacts($input){
          
          if(empty($input['chat']['id']) || empty($input['chat']['first_name']) && empty($input['chat']['last_name'])) return false;
          
          global $db;
          global $sys_tables;
          if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
          
          //проверяем есть ли уже этот пользователь в списке контактов
          $user_exists = $db->fetch("SELECT id FROM ".$sys_tables['telegram_contacts']." WHERE id_chat = ?",$input['chat']['id']);
          if(!$user_exists) $db->insertFromArray($sys_tables['telegram_contacts'],
                                                 array('id_chat'   => $input['chat']['id'], 
                                                       'firstname' => $input['chat']['first_name'], 
                                                       'lastname'  => $input['chat']['last_name'], 
                                                      )
                                                 );
      }
      
      //отправляем входящее сообщение на обработку
      public static function acceptMessage($input){
          $self = get_called_class();
          $message_id = $input['message_id'];
          $chat_id = $input['chat']['id'];
          if (isset($input['text']) && (time() - $input['date']  < 5) ) {
          //if (isset($input['text'])) { //for tests
              
              //при необходимости добавляем пользователя в список контактов
              $self::addUserToContacts($input);
              //логируем то что пришло
              $dialogline_id = self::logMessageFrom($input);
              
              $self = get_called_class();
              if(method_exists($self,"processMessage"))
                $self::processMessage($message_id,$chat_id,$input['text'],$dialogline_id);
              else return false;
          }
          else return false;
      }
      
      //отправка сообщений  
      /**
      * отправка сообщения в диалог
      * 
      * @param mixed $parameters
      */
      public static function sendMessage($parameters){
          return self::apiRequestPost("sendMessage",$parameters);
      }
      
      /**
      * пихаем что-нибудь в наш канал
      * 
      * @param mixed $message - текст или html, содержимое
      */
      public static function pushToChannel($message,$photo = false){
          $self = get_called_class();
          //определяем что передано, текст или HTML
          $parameters = array( 'chat_id' => self::$channel_id, "text" => ( !empty($message['content']) ? $message['content'] : $message ) );
          switch(true){
              case strlen(strip_tags($message)) == strlen($message) && empty($photo):
                return $self::apiRequestGet("sendMessage", $parameters);
                break;
              case strlen(strip_tags($message)) < strlen($message) || !empty($photo) :
                //sending photo
                if(!empty($photo)){
                    $photo_parameters = $parameters;
                    unset($photo_parameters['text']);
                    $photo_parameters['photo'] = $photo;
                    $photo_parameters['caption'] = $parameters['text'];
                    $result = $self::apiRequestGet("sendPhoto",$photo_parameters);
                }
                else{
                    $parameters['parse_mode'] = "HTML";
                    $result *= $self::apiRequestGet("sendMessage", $parameters);
                }
                return $result;
                break;
          }
      }
  }
  
  class Telegram extends TelegramController{
      
      private static $channel_id = "@bsn_ru";
      //private static $channel_id = -2147483648;
      //test
      //set webhook https://api.telegram.org/bot312438821:AAE8H5LMHVho5nthOCxkGgCfgsFiuBV1R7A/setWebhook?url=https://test.bsn.ru/telegramBot/AAE8H5LMHVho5nthOCxkGgCfgsFiuBV1R7A/
      //protected static $bot_token = '312438821:AAE8H5LMHVho5nthOCxkGgCfgsFiuBV1R7A';//токен бота для теста
      //protected static $webhook_url = "https://www.bsn.ru/telegramBot/AAE8H5LMHVho5nthOCxkGgCfgsFiuBV1R7A/";
      //protected static $api_url = 'https://api.telegram.org/bot312438821:AAE8H5LMHVho5nthOCxkGgCfgsFiuBV1R7A/';
      
      //production
      //set webhook https://api.telegram.org/bot619307156:AAGnZD5KFcDo2R4Y_dv3A38yL9hC3aV6Rcg/setWebhook?url=https://www.bsn.ru/telegramBot/AAGnZD5KFcDo2R4Y_dv3A38yL9hC3aV6Rcg/
      protected static $bot_token = '619307156:AAGnZD5KFcDo2R4Y_dv3A38yL9hC3aV6Rcg';//токен бота на продакшне
      protected static $webhook_url = "https://www.bsn.ru/telegramBot/AAGnZD5KFcDo2R4Y_dv3A38yL9hC3aV6Rcg/";
      protected static $api_url = 'https://api.telegram.org/bot619307156:AAGnZD5KFcDo2R4Y_dv3A38yL9hC3aV6Rcg/';
      
      //режимы работы
      private static $input_modes = array("search", "search_deal_type", "search_param", "search_result");
      //команды
      private static $commands = array('search_start' => array('Искать'),
                                       'search_end' => array('Отмена','Закончить поиск'),
                                       'search_param_end' => array('Закончить ввод'),
                                       'search_param_delete' => array('Очистить'),
                                       'search_param_rooms' => array('комната','студия','1ккв','2ккв','3ккв','4+ккв'),
                                       'search_result_more' => array('Еще'),
                                       'search_result_cheaper' => array('Дешевле'),
                                       'search_result_end' => array('Изменить условия')
                                       );
      //команды не меняющие режим работы
      private static $inmode_commands = array('search_param_delete','search_param_rooms');
      //соответствие кнопок командам
      private static $command_aliases = array('Поиск' => array('key' => '/search','description' => 'Начать поиск'),
                                              '/start' => array('key' => '/search','description' => 'Начать поиск'));
      
      //ключевые слова
      private static $keywords = array('cost' => array('от','до','>','<','='),
                                       'rooms' => array('комната','студия','1ккв','2ккв','3ккв','4+ккв'));
      
      //параметры поиска
      private static $search_array =  array('Цена' => array('title' => 'Задайте цену', 'key'=>'cost'),
                                            'Район' => array('title' => 'Задайте район', 'key' =>'districts'),
                                            'Метро' => array('title' => 'Задайте метро', 'key' => 'subways')
                                           );
      //клавиатуры разных режимов
      private static $keyboards = array(//клавиатура по умолчанию
                                        'default' => array('keyboard' => array(array('Поиск')),
                                                           'one_time_keyboard' => true,
                                                           'resize_keyboard' => true),
                                        //клавиатура результатов поиска
                                        'search_result' => array('keyboard' => array(array('Еще', 'Изменить условия', 'Закончить поиск')),
                                                                 'one_time_keyboard' => true,
                                                                 'resize_keyboard' => true),
                                        //клавиатура ввода параметров поиска
                                        'search_param' => array('keyboard' => array(array('Очистить','Закончить ввод')),
                                                                'one_time_keyboard' => true,
                                                                'resize_keyboard' => true),
                                        //клавиатура поиска
                                        'search' => array('keyboard' => array(array('комната','студия'),
                                                                              array('1ккв','2ккв','3ккв','4+ккв'),
                                                                              array('Цена', 'Район', 'Метро'),
                                                                              array('Искать','Отмена')),
                                                          'one_time_keyboard' => true,
                                                          'resize_keyboard' => true),
                                        //клавиатура ввода типа сделки
                                        'search_deal_type' => array('keyboard' => array(array("Снять посуточно"),
                                                                                        array("Снять на длительный срок"),
                                                                                        array("Купить"),
                                                                                        array('Отмена')),
                                                                    'one_time_keyboard' => true,
                                                                    'resize_keyboard' => true)
      );
      
      private static $result_block_length = 3;
      
      /**
      * фильтруем входящий текст. остаются только ключевые слова, команды, ключевые слова задания цены
      * 
      * @param mixed $text
      * @return mixed
      */
      private static function filterInputText($chat_id,$text){
          $keywords = call_user_func_array("array_merge",self::$commands);
          $mode = self::getActiveMode($chat_id);
          $allowed_keywords = call_user_func_array("array_merge",self::$keyboards[$mode]['keyboard']);
          $text = (!in_array($text,$allowed_keywords) &&                                           //ключевые слова допустимые для данного режима
                   !preg_match('/^(\/[0-9A-z]+)+$/ui',$text) &&                                    //команды к роботу
                   !($mode == "search" && in_array($text,self::$commands['search_param_rooms'])) &&//комнатность
                   !($mode == "search_param" && preg_match('/('.implode('|',self::$keywords['cost']).')\s?[0-9]+р?/sui',$text)) && //команды задания цены
                   !($text == "Hi" || $text == "Hello")                                            //команды котроля функционирования
                   ? "" : $text);
          if(!empty(self::$command_aliases[$text])) $text = self::$command_aliases[$text]['key'];
          return $text;
      }
      
      //обработка диалога с пользователем
      /**
      * обрабатываем сообщение. тут пока лежит логика
      * 
      * @param mixed $input - php://input
      */
      public static function processMessage($message_id,$chat_id,$input_text,$dialogline_id) {
          
          $text = self::filterInputText($chat_id,$input_text);
          
          switch(true){
              ///команды работающие во всех режимах
              //помощь
              case (strpos($text, "/help") === 0):
                $active_mode = self::getActiveMode($chat_id);
                self::sendMessage(array('chat_id' => $chat_id, 
                                                      "text" => 'Команды:', 
                                                      'reply_markup' => self::$keyboards[$active_mode])
                );
                break;
              //начало работы
              case (strpos($text, "/start") === 0):
                $active_mode = self::getActiveMode($chat_id);
                self::sendMessage(array('chat_id' => $chat_id, 
                                                          "text" => 'Hello', 
                                                          'reply_markup' => self::$keyboards[$active_mode])
                );
                break;
              //обработа некорректного ввода
              case (empty($text)):
                //получаем режим работы и показываем соответствующую клавиатуру
                $active_mode = self::getActiveMode($chat_id);
                self::sendMessage(array('chat_id' => $chat_id, 
                                                          "text" => "text '".$input_text."' accepted, no action",
                                                          'reply_markup' => self::$keyboards[$active_mode]));
                break;
              //тест, проверка функционирования
              case ($text === "Hello" || $text === "Hi"):
                self::sendMessage(array('chat_id' => $chat_id, 
                                                      "text" => 'Nice to meet you',
                                                      'reply_markup' => self::$keyboards["default"]));
                break;
              ///режим "по умолчанию"
              case !self::isInMode($chat_id,"search"):
                   switch(true){
                       //начало поиска
                       case (strpos($text,"/search") === 0):
                         self::sendMessage(array('chat_id' => $chat_id, 
                                                                  "text" => 'Что хотим?', 
                                                                  'reply_markup' => self::$keyboards['search_deal_type']));
                         self::startMode($chat_id,"search",$dialogline_id);
                         self::startMode($chat_id,"search_deal_type",$dialogline_id);
                       break;
                   }
                   break;
              ///режим ввода типа сделки
              case self::isInMode($chat_id,"search_deal_type"):
                    switch(true){
                       case (in_array($text,self::$commands['search_end'])):
                         self::endMode($chat_id,"search_deal_type");
                         self::endMode($chat_id,"search");
                         self::sendMessage(array('chat_id' => $chat_id,
                                                 "text" => "Поиск остановлен",
                                                 'reply_markup' => self::$keyboards['default']));
                       break;
                       //корректное значение, идем дальше
                       case (in_array($text,call_user_func_array("array_merge",self::$keyboards['search_deal_type']['keyboard']))):
                         self::sendMessage(array('chat_id' => $chat_id,
                                                                  "text" => 'Хотим '.$text,
                                                                  'reply_markup' => self::$keyboards['search']));
                         self::endMode($chat_id,"search_deal_type");
                       break;
                   }
                   break;
              ///режим вывода результатов поиска
              case self::isInMode($chat_id,"search_result"):
                    switch(true){
                        //выходим из режима вывода, к форме поиска
                        case in_array($text,self::$commands['search_result_end']):
                            self::endMode($chat_id,"search_result");
                            self::sendMessage(array('chat_id' => $chat_id,
                                                                  "text" => "можно редактировать запрос",
                                                                  'reply_markup' => self::$keyboards['search'])
                            );
                            break;
                        //выходим из режима вывода и режима поиска
                        case in_array($text,self::$commands['search_end']):
                            self::endMode($chat_id,"search_result");
                            self::endMode($chat_id,"search");
                            self::sendMessage(array('chat_id' => $chat_id,
                                                                  "text" => "Поиск остановлен",
                                                                  'reply_markup' => self::$keyboards['default'])
                            );
                            break;
                        //вывод следующего блока результатов
                        case in_array($text,self::$commands['search_result_more']):
                            $result = self::searchWithParams($chat_id,true);
                            self::sendMessage(array('chat_id' => $chat_id,
                                                                      "text" => (!empty($result) ? $result : "Больше вариантов нет"),
                                                                      'reply_markup' => self::$keyboards['search_result'],
                                                                      'disable_web_page_preview' => true)
                            );
                            break;
                    }
                    break;
              
              ///режим ввода значений параметров поиска
              case self::isInMode($chat_id,"search_param"):
                    $params_description = self::formulateSearchParams($chat_id);
                    switch(true){
                        //завершение ввода значений параметра
                        case in_array($text,self::$commands['search_param_end']):
                            self::endMode($chat_id,"search_param");
                            self::sendMessage(array('chat_id' => $chat_id,
                                                                      "text" => "Ввод параметра закончен, вы ищете: \r\n".$params_description,
                                                                      'reply_markup' => self::$keyboards['search'])
                            );
                            break;
                        //чистим введенные значения параметров
                        case in_array($text,self::$commands['search_param_delete']):
                            self::sendMessage(array('chat_id' => $chat_id,
                                                                      "text" => "Значения параметра очищены, вы ищете: \r\n".$params_description,
                                                                      'reply_markup' => self::$keyboards['search_param'])
                            );
                            break;
                        //ввод значения параметра
                        default:
                            self::sendMessage(array('chat_id' => $chat_id,
                                                                      "text" => "Значение принято, вы ищете: \r\n".$params_description,
                                                                      'reply_markup' => self::$keyboards['search_param'])
                            );
                    }
                    break;
              
              ///режим поиска
              case self::isInMode($chat_id,"search"):
                    switch(true){
                        //переход в режим ввода значений параметра (одна из кнопок Тип/Цена/Метро/Район...)
                        case (!empty(self::$search_array[$text]['title'])):
                            self::startMode($chat_id,"search_param",$dialogline_id);
                            if(!empty(self::$search_array[$text]['key'])) $param_values = self::getParamValues($chat_id,self::$search_array[$text]['key']);
                            
                            self::sendMessage(array('chat_id' => $chat_id, 
                                                                      "text" => "Для завершения ввода нажмите закончить.".(!empty($param_values) ? "\r\nПринимаемые значения:\r\n".$param_values : ""),
                                                                      'reply_markup' => array(
                                                                            'keyboard' => array(array(self::$commands['search_param_end'][0])),
                                                                            'one_time_keyboard' => true,
                                                                            'resize_keyboard' => true),
                                                                      "parse_mode" => "HTML"
                                                                      )
                            );
                            break;
                        //выдача вариантов по текущим параметрам ("Искать")
                        case in_array($text,self::$commands['search_start']):
                            $result = self::searchWithParams($chat_id);
                            self::startMode($chat_id,"search_result",$dialogline_id);
                            file_put_contents("modules/telegramBot/errors.log",date("d.m.Y H:i:s")." search params grabbed: ".json_encode($search_params)."\r\n\r\n",FILE_APPEND);
                            self::sendMessage(array('chat_id' => $chat_id,
                                                                      "text" => "результат:\r\n".(!empty($result) ? $result : "Больше вариантов нет"),
                                                                      'reply_markup' => self::$keyboards['search_result'])
                            );
                            break;
                        //выход из режима поиска ("Отмена")
                        case in_array($text,self::$commands['search_end']):
                            self::endMode($chat_id,"search");
                            self::sendMessage(array('chat_id' => $chat_id,
                                                                  "text" => "Поиск остановлен",
                                                                  'reply_markup' => self::$keyboards["default"]));
                            break;
                        //ввод значений комнатности
                        case in_array($text,self::$commands['search_param_rooms']):
                            $params_description = self::formulateSearchParams($chat_id);
                            self::sendMessage(array('chat_id' => $chat_id,
                                                                  "text" => $params_description,
                                                                  'reply_markup' => self::$keyboards['search'])
                            );
                            break;
                        default:
                            self::sendMessage(array('chat_id' => $chat_id, 
                                                                      "text" => "Выберите нужные параметры",
                                                                      'reply_markup' => self::$keyboards['search'])
                            );
                    }
                    break;
              default:
                self::sendMessage(array('chat_id' => $chat_id,
                                                      "text" => "Список команд:
                                                                 'Поиск' - начать поиск недвижимости",
                                                      'reply_markup' => self::$keyboards['default']));
          }
      }
      
      /**
      * получаем список значений выбранного параметра, готовый к выводу
      * 
      * @param mixed $param_name
      */
      private static function getParamValues($chat_id,$param_name){
          
          global $db;
          global $sys_tables;
          if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
          
          if($param_name == 'type_objects') $param_name = "type_objects_live";
          if(empty($param_name) || empty($sys_tables[$param_name])) return false;
          
          $param_values = $db->fetchall("SELECT title FROM ".$sys_tables[$param_name]." ORDER BY title ASC",'title');
          $param_values = array_keys($param_values);
          $result = "";
          
          //читаем уже введенное
          $selected_params = self::getSearchParams($chat_id);
          
          foreach($param_values as $key=>$item) $result .= (in_array(("/".$key),$selected_params[$param_name]) ? "/".$key."r <b>".$item."</b>" : "/".$key." ".$item)."\r\n";
          
          return $result;
      }
      
      //переключение режимов работы
      /**
      * отмечаем в списке контактов id строки диалога, с которой начался ввод параметров поиска
      * 
      * @param mixed $chat_id
      * @param mixed $dialogline_id
      */
      private static function startMode($chat_id,$mode,$dialogline_id = false){
          if(empty($chat_id) || !in_array($mode,self::$input_modes)) return false;
          
          global $db;
          global $sys_tables;
          if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
          if(empty($dialogline_id)){
              $dialogline_id = $db->fetch("SELECT id FROM ".$sys_tables['telegram_dialogs']." WHERE id_chat = ? ORDER BY id DESC",$chat_id);
              if(empty($dialogline_id) || empty($dialogline_id['id'])) return false;
              else $dialogline_id = $dialogline_id['id'];
          }
          
          $db->querys("UPDATE ".$sys_tables['telegram_contacts']." SET ".$mode."_start_id = ? WHERE id_chat = ?",$dialogline_id,$chat_id);
      }
      /**
      * останавливаем поиск, чистим поле
      * 
      * @param mixed $chat_id
      */
      private static function endMode($chat_id,$mode){
          
          if(empty($chat_id) || !in_array($mode,self::$input_modes)) return false;
          
          global $db;
          global $sys_tables;
          if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
          
          $db->querys("UPDATE ".$sys_tables['telegram_contacts']." SET ".$mode."_start_id = 0 WHERE id_chat = ?",$chat_id);
      }
      /**
      * проверяем, идет ли поиск
      * 
      * @param mixed $chat_id
      * @return mixed
      */
      private static function isInMode($chat_id,$mode){
          if(empty($chat_id) || !in_array($mode,self::$input_modes)) return false;
          
          global $db;
          global $sys_tables;
          if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
          
          //читаем id строки с которой начнем собирать поиск
          $id_start = $db->fetch("SELECT ".$mode."_start_id FROM ".$sys_tables['telegram_contacts']." WHERE id_chat = ?",$chat_id);
          return !(empty($id_start) || empty($id_start[$mode.'_start_id']));
      }
      /**
      * получаем активный режим работы
      * 
      * @param mixed $chat_id
      */
      private static function getActiveMode($chat_id){
          if(empty($chat_id)) return false;
          
          global $db;
          global $sys_tables;
          if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
          
          //читаем активные режимы
          $chat_values = $db->fetch("SELECT * FROM ".$sys_tables['telegram_contacts']." WHERE id_chat = ?",$chat_id);
          $active_modes = array();
          foreach(self::$input_modes as $key=>$mode_alias){
              if(!empty($chat_values[$mode_alias."_start_id"])) array_push($active_modes,$mode_alias);
          }
          if(empty($active_modes)) return "default";
          
          //разделяем общие режимы и подрежимы
          $general_modes = array_values(array_filter($active_modes,function($v){ return preg_match('/^[a-zA-Z]+$/ui',$v);} ));
          $sub_modes = array_values(array_diff($active_modes,$general_modes));
          
          //активный режим может быть только один. это может быть либо общий режим(напр. "search"), либо подрежим ("search_param"), либо "default"
          $active_mode = (!empty($sub_modes) ? $sub_modes[0] : (!empty($general_modes) ? $general_modes[0] : "default"));
          
          return $active_mode;
      }
      
      //обработка поискового запроса
      /**
      * получаем из диалога текущие параметры поиска
      * 
      * @param mixed $chat_id
      */
      public static function getSearchParams($chat_id){
          
          if(empty($chat_id)) return false;
          
          global $db;
          global $sys_tables;
          if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
          
          //читаем id строки с которой начнем собирать поиск
          $id_start = $db->fetch("SELECT search_start_id FROM ".$sys_tables['telegram_contacts']." WHERE id_chat = ?",$chat_id);
          if(empty($id_start) || empty($id_start['search_start_id'])) return false;
          else $id_start = $id_start['search_start_id'];
          
          //читаем строки диалога относящиеся к поиску
          $search_input = $db->fetchall("SELECT message,to_from FROM ".$sys_tables['telegram_dialogs']." WHERE id > ? AND id_chat = ? AND to_from = 2 ORDER BY id DESC",false,$id_start,$chat_id);
          $search_params = array();
          $search_params_active_key = "";
          $keywords = call_user_func_array("array_merge",self::$commands);
          //набираем параметры
          while(!empty($search_input)){
              $item = array_pop($search_input);
              
              //если это ключевое слово, меняющее режим работы, continue. 
              //Ключевые слова, не меняющие режим работы должны обрабатываться здесь же
              if(in_array($item['message'],$keywords)){
                  $command = key(array_filter(self::$commands,function($v) use($item){return in_array($item['message'],$v);}));
                  if($command == "search_param_end") $search_params_active_key = "";
                  if(!in_array($command,self::$inmode_commands)) continue;
              } 
              //тип сделки задается отдельно
              if(in_array($item['message'],call_user_func_array("array_merge",self::$keyboards['search_deal_type']['keyboard']))){
                  $search_params['rent'] = ($item['message'] == "Купить" ? 2 : 1);
                  $search_params['by_the_day'] = ($item['message'] == "Снять посуточно" ? 1 : 2);
                  continue;
              }
              //если это ключ, запоминаем его, последующие значения(не-ключи) будет значением этого ключа
              if(!empty(self::$search_array[$item['message']])){
                  $search_params_active_key = self::$search_array[$item['message']]['key'];
                  if(empty($search_params[$search_params_active_key])) $search_params[$search_params_active_key] = array();
              }
              //метка удаления - удаляем значения этого параметра которые были накоплены
              elseif(in_array($item['message'],self::$commands['search_param_delete'])) $search_params[$search_params_active_key] = array();
              //удалаяем значение параметра (когда кликнули по выбранному)
              elseif(preg_match('/^\/[0-9]+r$/ui',$item['message'])) $search_params[$search_params_active_key] = array_diff($search_params[$search_params_active_key],array($item['message'],str_replace('r','',$item['message'])));
              //добавляем значение параметра
              elseif(!empty($search_params_active_key)) $search_params[$search_params_active_key][] = $item['message'];
              elseif(!empty($command)) $search_params[$command] = $item['message'];
          }
          return $search_params;
      }
      /**
      * переводим введенные параметры в удобную для нас форму
      * 
      */
      private static function processSearchParams($search_params){
          
          if(empty($search_params)) return false;
          
          global $db;
          global $sys_tables;
          if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
          
          $params = array_fill_keys(array_keys($search_params));
          
          $params['rent']['value'] = $search_params['rent'];
          
          //обрабатываем цену
          if(!empty($search_params['cost'])){
              foreach($search_params['cost'] as $key=>$value){
                  $cost_value = preg_replace('/[^0-9]/sui','',$value);
                  switch(true){
                      case preg_match('/до|\</sui',$value):
                        $params['cost']['to'] = $cost_value;
                        break;
                      case preg_match('/от|\>/sui',$value):
                        $params['cost']['from'] = $cost_value;
                        break;
                      default:
                        $params['cost']['value'] = $cost_value;
                  }
              }
          }
          
          $params['by_the_day']['value'] = $search_params['by_the_day'];
          
          //обрабатываем комнатность и тип объекта
          if(!empty($search_params['search_param_rooms'])){
              $rooms = $search_params['search_param_rooms'];
              switch(true){
                  case in_array($rooms,array("1ккв","2ккв","3ккв","4+ккв")):
                    $params['id_type_object']['value'] = 1;
                    $params['rooms_sale']['value'] = preg_replace('/[^0-9]/sui','',$rooms);
                    break;
                  //студия только если квартиры
                  case $rooms == "студия":
                    $params['id_type_object']['value'] = 1;
                    $params['rooms_sale']['value'] = 0;
                    break;
                  case $rooms == "комната":
                    $params['id_type_object']['value'] = 2;
                    $params['rooms_sale']['value'] = 1;
                    break;
              }
          }
          
          //обрабатываем район
          if(!empty($search_params['districts'])){
              //вычленяем те строки, в которых есть указания цифрами:
              $lines_accepted = array_filter($search_params['districts'],function($v){return preg_match('/\/[0-9]+/sui',$v);});
              
              if(!empty($lines_accepted)){
                  //читаем список в котором давалось пользователю, чтобы получить соответствие номер->название
                  $districts = $db->fetchall("SELECT id FROM ".$sys_tables['districts']." ORDER BY title ASC",'id');
                  $districts = array_keys($districts);
                  $selected_districts_ids = array();
                  //получаем введенные цифры
                  foreach($lines_accepted as $line_key=>$line_value){
                      preg_match_all('/(?<=\/)[0-9]+/sui',$line_value,$digits);
                      $new_ids = array_intersect_key($districts,array_flip(array_pop($digits)));
                      $selected_districts_ids = array_merge($selected_districts_ids,$new_ids);
                  }
                  
              }
              $params['id_district']['set'] = $selected_districts_ids;
          }
              
          
          //обрабатываем метро
          if(!empty($search_params['subways'])){
              //вычленяем те строки, в которых есть указания цифрами:
              $lines_accepted = array_filter($search_params['subways'],function($v){return preg_match('/\/[0-9]+/sui',$v);});
              
              if(!empty($lines_accepted)){
                  //читаем список в котором давалось пользователю, чтобы получить соответствие номер->название
                  $subways = $db->fetchall("SELECT id FROM ".$sys_tables['subways']." ORDER BY title ASC",'id');
                  $subways = array_keys($subways);
                  $selected_districts_ids = array();
                  //получаем введенные цифры
                  foreach($lines_accepted as $line_key=>$line_value){
                      preg_match_all('/(?<=\/)[0-9]+/sui',$line_value,$digits);
                      $new_ids = array_intersect_key($subways,array_flip(array_pop($digits)));
                      $selected_districts_ids = array_merge($selected_districts_ids,$new_ids);
                  }
                  
              }
              $params['id_subway']['set'] = $selected_districts_ids;
          }
              
          
          return $params;
      }
      /**
      * фраза-описание параметров поиска
      * 
      * @param mixed $search_params
      */
      private static function formulateSearchParams($chat_id){
          if(empty($chat_id)) return false;
          if($from === true) $from = self::getShownResultBlocksAmount($chat_id);
          
          //читаем
          $search_params = self::getSearchParams($chat_id);
          //готовим к использованию
          $search_params = self::processSearchParams($search_params);
          
          require_once('includes/class.estate.subscriptions.php');
          $result = EstateSubscriptions::getTelegramTitle($search_params);
          
          return $result;
      }
      /**
      * считаем сколько блоков поисковых результатов уже показано
      * 
      * @param mixed $chat_id
      */
      private static function getShownResultBlocksAmount($chat_id){
          if(empty($chat_id)) return false;
          
          global $db;
          global $sys_tables;
          if(empty($sys_tables)) $sys_tables = Config::$values['sys_tables'];
          
          $results_from = $db->fetch("SELECT search_result_start_id FROM ".$sys_tables['telegram_contacts']." WHERE id_chat = ?",$chat_id);
          if(empty($results_from)) return false;
          else $results_from = $results_from['search_result_start_id'];
          
          $result = $db->fetch("SELECT COUNT(*) AS amount FROM ".$sys_tables['telegram_dialogs']." WHERE id >= ? AND id_chat = ? AND to_from = 1",$results_from,$chat_id);
          return (empty($result) || empty($result['amount']) ? false : $result['amount']);
      }
      /**
      * поиск в базе (пока только по жилой недвижимости)
      * 
      * @param mixed $chat_id
      */
      private static function searchWithParams($chat_id,$from = false){
          
          if(empty($chat_id)) return false;
          if($from === true) $from = self::getShownResultBlocksAmount($chat_id);
          
          //читаем
          $search_params = self::getSearchParams($chat_id);
          //готовим к использованию
          $search_params = self::processSearchParams($search_params);
          
          $estateList = new EstateListLive();
          $search_results = $estateList->SearchTelegram($search_params,self::$result_block_length,(!empty($from) ? $from * self::$result_block_length : 0),"date_change","group_id");
          
          return self::formulateSearchResults($search_results);
      }
      /**
      * представляем результаты поиска в удобном виде
      * 
      * @param mixed $search_results
      */
      private static function formulateSearchResults($search_results){
          $results = array();
          foreach($search_results as $key=>$item){
              $results[$key]['object_description'] = $item['obj_type'];
              $results[$key]['address'] = $item['full_address'];
              //убрали район из результатов
              if(!empty($item['subway']) || !empty($item['district'])) $results[$key]['subway_district'] = '🚇'." ".$item['subway'];
              $results[$key]['cost'] = $item['cost']."р".($item['rent'] == 1 ? (!empty($item['by_day']) ? "/сутки" : "/мес") : "");
              $results[$key]['seller'] = '📞'.$item['seller_phone']." ".(!empty($item['agency_title']) ? $item['agency_title'] : $item['seller_name']);
              $results[$key]['url'] = $item['url'];
              $results[$key] = implode("\r\n",$results[$key]);
          }
          
          return implode("\r\n\r\n",$results);
      }
  }
?>