<?php
class SMSSender{
    public $provider = 'MirSms';
    public $author = 'BSN.ru';
    public $request_url = 'http://web.mirsms.ru/public/http/z.php';
    public $login = '26444';
    public $password = '78473634';
    public $content_type = 'application/x-www-form-urlencoded';
    public $content_charset = 'UTF-8';
    public $xml_sms_in_packet = 250;
    public $db_sms_log_table = 'common.sms_log';
    public $db_sms_text_table = 'common.sms_text';
    
    private $xml_sms_send_buffer = '';
    private $ids_sms_send_buffer = [];
    private $xml_sms_status_buffer = '';
    private $ids_sms_status_buffer = [];
    
    /**
    * отправка СМС по указанным телефонам
    * 
    * @param string $text - текст сообщения
    * @param array $phones - массив номеров телефонов адрессатов
    * @param integer $ttl - время жизни смс в минутах
    * @return integer - кол-во успешно отправленных сообщений (false если рассылка не корректна/ошибочна)
    */
    public function smsSend($text, $phones, $ttl=60){
        global $db;
        if(empty($text)) return false;
        if(empty($phones)) return false;
        // экранируем текст (требование смс-портала)
        $text = htmlentities($text, ENT_QUOTES,$this->content_charset);
        // создаем запись рассылки
        $send_id = $this->createText($text, sizeof($phones));
        if(empty($send_id)) return false;
        
        // очистка буффера
        $this->clearBuffer('send');
        // инициализация счетчика смс
        $counter = $this->xml_sms_in_packet;
        // общий счетчик адрессатов
        $total_counter = sizeof($phones);
        // счетчик успешно отправленных смс
        $success_counter = 0;
        // обработка всех номеров в цикле
        foreach($phones as $phone){
            // добавляем смс в xml-буффер
            $this->smsAddToSendBuffer($send_id, $text, $phone, $ttl);
            $counter--;
            $total_counter--;
            // если набрали максимальное кол-во смс для пакета или кончились адрессаты
            if($counter<1 || $total_counter<1) {
                // формируем xml- запрос
                $xml = $this->makeXMLrequest('send');
                // отправляем запрос на смс-портал
                $result_xml = $this->makeRequest($xml);
                // анализируем ответ смс-портала
                $success_counter += $this->checkResponse($send_id, $result_xml);
                // очистка буффера
                $this->clearBuffer('send');
                // переустановка счетчика смс
                $counter = $this->xml_sms_in_packet;
            }
        }
        // записываем результат рассылки
        $db->querys("UPDATE ".$this->db_sms_text_table."
                    SET `status`='OK', `success`=?
                    WHERE id=?"
                    , $success_counter
                    , $send_id);
        return $success_counter;
    }
    
    /**
    * проверка статуса указанных смс
    * @param array $ids id смс во внутренней DB
    * @return array $id=>$status статусы
    */
    public function smsCheckStatus($ids){
        global $db;
        if(empty($ids)) return false;
        $return = [];
        // очистка буффера
        $this->clearBuffer('status');
        // инициализация счетчика смс
        $counter = $this->xml_sms_in_packet;
        // общий счетчик id
        $total_counter = sizeof($ids);
        // получение информации об смс-ках с запрошенными id
        $sms_info = $db->fetchall("SELECT * FROM ".$this->db_sms_log_table."
                                   WHERE `id` IN (".implode(',',$ids).") AND `code`>=0 AND LENGTH(`push_id`)>0");
        // обработка всех смс
        foreach($sms_info as $sms){
            // добавляем смс в xml-буффер
            $this->smsAddToStatusBuffer($sms['id'], $sms['push_id']);
            $counter--;
            $total_counter--;

            // если набрали максимальное кол-во смс для пакета или кончились адрессаты
            if($counter<1 || $total_counter<1) {
                // формируем xml- запрос
                $xml = $this->makeXMLrequest('status');
                // отправляем запрос на смс-портал
                $result_xml = $this->makeRequest($xml);
                // анализируем ответ смс-портала
                $return += $this->checkStatusResponse($result_xml);
                // очистка буффера
                $this->clearBuffer('status');
                // переустановка счетчика смс
                $counter = $this->xml_sms_in_packet;
            }
        }
        return $return;
    }
    
    /**
    * проверка xml-ответа - результата проверки статуса отправленных смс
    * @param string $xml_string - xml с ответом смс-портала
    * @return array (id=>(status[,description]),...)
    */
    private function checkStatusResponse($xml_string){
        global $db;
        $return = [];
        $xml = simplexml_load_string($xml_string);
        if(empty($xml)) return []; // вобще не возможно разобрать xml
        $xml_result = (string)$xml->attributes()->res;
        if(!empty($xml_result)) {
            // результат с ошибкой... получаем описание ошибки
            $xml_result_desc = (string)$xml->attributes()->description;
            return $return;
        }
        $smses = $xml->xpath('sms');
        foreach($smses as $sms){
            $push_id = (string)$sms->attributes()->push_id;
            $status = intval((string)$sms->attributes()->status);
            $description = (string)$sms->attributes()->description;
            if(empty($description)){
                switch($status){
                    case 0: $description = 'Принято к обработке в '.$this->provider; break;
                    case 1: $description = 'SMS помещена в очередь на отправку'; break;
                    case 2: $description = 'SMS передана оператору связи и ожидает доставку'; break;
                    case 4: $description = 'SMS доставлена'; break;
                    case -1: $description = 'Указан не существующий push_id сообщения'; break;
                    case -1004: $description = 'SMS не доставлена'; break;
                }
            }
            $delivery_time = (string)$sms->attributes()->delivery_time;
            $delivery_date = (string)$sms->attributes()->delivery_date;
            if(!empty($delivery_time) && !empty($delivery_date)) {
                list($dd,$mm,$yyyy) = explode('.',$delivery_date);
                $delivery_datetime = $yyyy.'-'.$mm.'-'.$dd.' '.$delivery_time;
            } else $delivery_datetime = '0000-00-00 00:00:00';
            if(!empty($this->ids_sms_status_buffer[$push_id])){
                $db->querys("UPDATE ".$this->db_sms_log_table."
                            SET `code`=?, `status`=?, delivery_datetime=? 
                            WHERE `id`=?"
                            , $status
                            , $description
                            , $delivery_datetime
                            , $this->ids_sms_status_buffer[$push_id]);
                $return[$this->ids_sms_status_buffer[$push_id]] = $status;
            }
        }
        return $return;
    }
    
    /**
    * проверка xml-ответа - результата отправки пакета смс
    * @param integer $send_id 
    * @param mixed $xml_string
    * @return mixed
    */
    private function checkResponse($send_id, $xml_string){
        global $db;
        $xml = simplexml_load_string($xml_string);
        if(empty($xml)) return false; // вобще не возможно разобрать xml
        $xml_result = (string)$xml->attributes()->res;
        if(!empty($xml_result)) {
            // результат с ошибкой... получаем описание ошибки
            $xml_result_desc = (string)$xml->attributes()->description;
            return 0;
        }
        $success_count = 0;
        // результаты приема смс-ок
        $pushes = $xml->xpath('push');
        foreach($pushes as $push){
            if((string)$push->attributes()->res != '0'){
                // ошибочный статус
                $db->querys("UPDATE ".$this->db_sms_log_table."
                            SET `code`=?, `status`=?
                            WHERE `id`=?"
                            , intval((string)$push->attributes()->res)
                            , (string)$push->attributes()->description
                            , intval((string)$push->attributes()->sms_id));
            } else {
                // смс отправлена
                $db->querys("UPDATE ".$this->db_sms_log_table."
                            SET `sms_count`=?, `push_id`=?, `code`=0
                            WHERE `id`=?"
                            , intval((string)$push->attributes()->sms_count)
                            , (string)$push->attributes()->push_id
                            , intval((string)$push->attributes()->sms_id));
                $success_count++;
            }
        }
        return $success_count;
    }
    
    private function makeRequest($xml){
        $chanel = curl_init($this->request_url);
        curl_setopt($chanel, CURLOPT_HEADER, 0);
        curl_setopt($chanel, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($chanel, CURLOPT_POST, 1);
        curl_setopt($chanel, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($chanel, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($chanel, CURLOPT_SSL_VERIFYHOST, 0);
        $result_xml = curl_exec($chanel);
        if( curl_errno($chanel) != 0 ){
            return 'CURL_error: ' . curl_errno($chanel) . ', ' . curl_error($chanel);
        }
        curl_close($chanel);
        return $result_xml;
    }
    
    /**
    * очистка xml-буффера смс
    * @param string тип xml-буффера [send|status|both]
    */
    private function clearBuffer($type='send'){
        if($type=='send' || $type=='both'){
            $this->xml_sms_send_buffer = '';
            $this->ids_sms_send_buffer = [];
        }
        if($type=='status' || $type=='both'){
            $this->xml_sms_status_buffer = '';
            $this->ids_sms_status_buffer = [];
        }
    }
    
    /**
    * добавление смс в xml-буффер
    * 
    * @param mixed $send_id идентификатор рассылки (текста)
    * @param mixed $text текст смс
    * @param mixed $number номер адрессата
    * @param mixed $ttl время жизни смс (в минутах)
    * @return boolean
    */
    private function smsAddToSendBuffer($send_id, $text, $number, $ttl){
        global $db;
        $res = $db->querys("INSERT INTO ".$this->db_sms_log_table." (id_text, number, ttl)
                           VALUES (?, ?, ?)"
                          , $send_id
                          , $number
                          , $ttl);
        if(empty($res)) return false;
        $sms_id = $db->insert_id;
        $this->xml_sms_send_buffer .= sprintf('<sms sms_id="%d" number="%s" source_number="%s" ttl="%d">%s</sms>', $sms_id, $number, $this->author, $ttl, $text);
        $this->ids_sms_send_buffer[] = $sms_id;
        return true;
    }

    /**
    * добавление смс в буффер проверки статуса
    * 
    * @param mixed $sms_id внутренний ID смс
    * @param mixed $push_id идентификатор ранее переданного SMS в системе ESME
    */
    private function smsAddToStatusBuffer($sms_id, $push_id){
        $this->xml_sms_status_buffer .= sprintf('<sms push_id="%s"/>', $push_id);
        $this->ids_sms_status_buffer[$push_id] = $sms_id;
        return true;
    }
        
    /**
    * формирование xml-запроса на отправку пакета смс
    * @param string тип xml [send|status]
    * @return string XML
    */
    private function makeXMLrequest($type='send'){
        $xml = '<?xml version="1.0" encoding="'.$this->content_charset.'" ?>'
               .'<xml_request name="sms_'.($type=='send' ? 'send' : 'status2').'">'
               .'<xml_user lgn="'.$this->login.'" pwd="'.$this->password.'"/>'
               .($type=='send' ? $this->xml_sms_send_buffer : $this->xml_sms_status_buffer)
               .'</xml_request>';
        return $xml;
    }
    
    /**
    * создание записи для рассылки
    * @param string $text - текст смс
    * @return integer ID рассылки (false if error)
    */
    private function createText($text, $count=0){
        global $db;
        if(empty($text)) return false;
        $result = $db->querys("INSERT INTO ".$this->db_sms_text_table."
                                (`text`, `from_who`, `count`, `create_date`)
                              VALUES
                                (?, ?, ?, NOW())"
                              , $text
                              , $this->author
                              , $count);
        if($result) return $db->insert_id;
        return false;
    }
}
?>
