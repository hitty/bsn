<?php
/**    
* Основной класс обработки запросов
*/

class Comagic {
    public static $session_key = '';            // идентификатор сессии
    public static $phones = [];            // список виртуальных номеров

    /**
    * получение ключа при авторизации / из сессии
    */
    public static function Init(){
        global $db;
        //$session_key = Session::GetString('comagic_session_key');
        if(!empty($session_key)) self::$session_key = $session_key;
        else{
            
            //параметры подключения
            $url = "http://api.comagic.ru/api/login/";
            $params = array(
                'login' =>  Config::Get('comagic/login'),
                'password' => Config::Get('comagic/password')
            );
            //отправка запроса и получение 
            $result = curlThis($url, 'POST', $params, true); 
            $result = json_decode($result, true);
            if(!empty($result['success'])){
                self::$session_key = $result['data']['session_key'];
                Session::SetString('comagic_session_key',self::$session_key);
            }       
        }
        self::Phones();
    }

    /**
    * Получение списка виртуальных номеров
    * @param string $location
    * @return array
    */
    public static function Phones() {
        $url = "http://api.comagic.ru/api/v1/customer_list/?session_key=".self::$session_key;
        $result = curlThis($url, 'GET', false, true); 
        return $result;
    }    
   
}
?>