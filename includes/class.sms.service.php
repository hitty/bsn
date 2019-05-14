<?php
class SmsService {
    private $sms_url = "https://semysms.net/api/3/sms.php";
    private $devices_url = "https://semysms.net/api/3/devices.php";
    private $outpost_sms = "https://semysms.net/api/3/outbox_sms.php";
    private $token = "3b7732c0f58e458316075c3d5962b024";
    
    public function __construct(){
    }
    
    //curl-запрос
    private function curl_send($url,$data){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data)
        ));
        $response = json_decode(curl_exec($curl));
        curl_close($curl);        
        return $response;
    }

    //список активных устройств    
    private function getDevicesList($status = false){
        $data = array("token" => $this->token);
        if(!empty($status) && in_array($status,array(0,1))) $data['is_archive'] = $status;
        $response = $this->curl_send($this->devices_url,$data);
        if(empty($response)) return false;
        else return $response->data;
    }
    
    //шлем СМС
    public function sendSmsMessage($reciever_number,$text){
        
        $active_devices = $this->getDevicesList(0);
        if(empty($active_devices)) return false;
        
        $active_device = array_pop($active_devices);
        
        $data = array(
            "phone" => $reciever_number,
            "msg" => $text,
            "device" => $active_device['id'],
            "token" => $this->token
        );
        $response = $this->curl_send($this->sms_url,$data);
        
        return (isset($response->code) && !empty($response->id));
    }
    public function getSendedSmsList(){
        "token - секретный ключ доступа к API (обязательный параметр)
device - код устройства из списка подключенных ваших устройств (обязательный параметр)";
        $devices = $this->getDevicesList();
        foreach($devices as $key=>$device){
            $data = array("token" => $this->token,"device"=>$device['id']);
            $response[$device['id']] = $this->curl_send($this->outpost_sms,$data);
        }
        return $response;
    }
}      

?>