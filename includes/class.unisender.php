<?php
class UnisenderAPI {
    private $apiKey = '617u4of56fbc688j6ewnq9adgy7fu19kar7bkjny';
    private $apiUrl = 'https://api.unisender.com/ru/api/';

    public function __construct() {
    }

    /**
     * Добавление пользователя в адресную книгу
     *
     * @param string $email Email пользователя
     * @param string $listId ID адресной книги
     * @param array $fields Дополнительные поля (например, имя, телефон)
     * @return array Ответ API
     */
    public function importContacts($field_names, $data) {
        $params = [
            'api_key' => $this->apiKey,
            'field_names' => $field_names,
            'data' => $data,
        ];

        return $this->sendRequest('importContacts', $params);
    }
    public function sendEmail($mailer_title, $html, $sender_name, $sender_email, $emails) {
        foreach( $emails as $email ) {
            $params = [
                'api_key' => $this->apiKey,
                'email' => $email['email'],
                'sender_name' => $sender_name,
                'sender_email' => $sender_email,
                'subject' => $mailer_title,
                'body' => $html,
                'list_id' => 1
            ];
        }

        return $this->sendRequest('sendEmail', $params);
    }

    /**
     * Отправка запроса к API Unisender
     *
     * @param string $method Метод API
     * @param array $params Параметры запроса
     * @return array Ответ API
     */
    private function sendRequest($method, $params) {
        $url = $this->apiUrl . $method . '?format=json';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}