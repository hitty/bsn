<?php
require_once('ipooption.php');

/**
 * Реализация класса для запросов экспортных данныхот Интернет-сервиса IPOhelp.ru
 *
 * @author Александр Воробьев avorobiev@ipohelp.ru
 * @version 1.0
 * @package ipohelp_api
 */

class ipoXmlClient extends ipoOption
{
    private
        // Название темы для запроса к интернет-сервесу
        $requestThemeName,

        // Параметры запроса (будут переданы POST-запросом Интернет-сервису)
        $requestParams = [],

        // Данные заголовков ответа Expires" от запроса к Интернет-сервису
        $responseExpire = false,

        // Название темы, полученной в ответ на запрос
        $responseThemeName
    ;

    protected
        // Ответ в формате DOMDocument либо false
        $xml
    ;

    /**
     * Определение параметров класса по умолчанию
     *
     * @return []      Параметры класса по умолчанию
     */
    protected function getDefaultOptions()
    {
        return array(
            // Основная точка доступа к сервису
            'mirror1' => 'http://xml1.ipohelp.ru',

            // Резервная точка доступа к сервису
            'mirror2' => 'http://xml2.ipohelp.ru',

            // Код паротнера в системе IPOhelp.ru
            'pid' => '',

            // Список допустимых тем запросов (кроме перечисленных тем допустим запрос с кодом автообъявления)
            'allowed_request_themes' => array('queryform', 'queryresult', 'programdescription', 'banklist', 'calccreditsizes', 'calcinstallments'),

            // Параметры CURL
            'CURL' => array(
                //CURLOPT_TIMEOUT => 10         // Ожидать ответа в течение 10 секунд
            )
        );
    }

    /**
     * Установка темы запроса
     *
     * @param mixed $requestThemeName     Тема, либо код автообъявления
     * @return true
     */
    public function setRequestThemeName($requestThemeName)
    {
        $allowedRequestThemes = $this->getOption('allowed_request_themes');

        if ((is_array($allowedRequestThemes) && in_array($requestThemeName, $allowedRequestThemes))) {
            $this->requestThemeName = $requestThemeName;
        } else {
            throw ipoException::getException(ipoException::ERR_WRONG_REQUEST_THEME, $requestThemeName);
        }

        return true;
    }

    /**
     * Получение темы запроса
     *
     * @return string
     * @throws ipoException
     */
    public function getRequestThemeName()
    {
        if (!$this->requestThemeName) {
            throw ipoException::getException(ipoException::ERR_DOES_NOT_SET_REQUEST_THEME);
        }

        return $this->requestThemeName;
    }

    /**
     * Установка параметров, которые должны быть переданы Интернет-сервису MaxPoster
     *
     * @param array $params
     */
    public function setRequestParams($params = null)
    {
        $this->requestParams = $params;

        return true;
    }

    /**
     * Возвращает параметры, которые должны быть перенады Интернет-сервису MaxPoster
     *
     * @return array
     */
    public function getRequestParams()
    {
        return $this->requestParams;
    }

    /**
     * Формирование относительного пути для запроса к Интернет-сервису IPOhelp.ru
     *
     * @param string $_requestThemeName    Название темы запроса
     * @return string   Отсносительный путь для запроса к Интернет-сервису MaxPoster
     */
    protected function getRelativePath()
    {
        $partnerId = $this->getOption('pid');

        if (!$partnerId) {
            throw ipoException::getException(ipoException::ERR_DOES_NOT_SET_PARTNER_ID);
        }

        $query = http_build_query(
            array_merge($this->getRequestParams(),
            array(
                'pid' => $partnerId,
                'whatisneed' => $this->getRequestThemeName()
            )
        ));

        return '/iposearch2.php'.(!empty($query) ? '?'.$query : '');
    }

    /**
     * Формирование абсолютного пути для запроса к Интернет-сервису IPOhelp.ru
     *
     * @param string $mirror    Допустимые значения mirror1 | mirror2
     * @return string   Путь к Интернет-Сервису
     */
    protected function getAbsolutePath($mirror)
    {
        $url = $this->getOption($mirror);

        if (empty($url)) {
          throw ipoException::getException(ipoException::ERR_DOES_NOT_SET_MIRROR, $mirror);
        }

        return $url.$this->getRelativePath();
    }

    /**
     * Формирование абсолютного пути к основному зеркалу Интернет-сервиса MaxPoster
     *
     * @return string   Путь к Интернет-Сервису
     */
    protected function getPathToFirstMirror()
    {
        return $this->getAbsolutePath('mirror1');
    }

    /**
     * Формирование абсолютного пути к запасному зеркалу Интернет-сервиса MaxPoster
     *
     * @return string   Путь к Интернет-Сервису
     */
    protected function getPathToSecondMirror()
    {
        return $this->getAbsolutePath('mirror2');
    }

    /**
     * Извлечение из заголовков ответа времени актуальности данных (Expires).
     *
     * @param object $ch
     * @param string $header
     * @return int
     */
    protected function setResponseExpire($ch, $header)
    {
        if (false!== strpos($header, 'Expires: ')) {
            $this->responseExpire = strtotime(substr($header, strlen('Expires: ')));
        }

        return strlen($header);
    }

    /**
     * Возвращает таймстамп времени, до которого данные считаются актуальными.
     *
     * @return timestamp  срок актуальности
     */
    protected function getResponseExpire()
    {
        return $this->responseExpire;
    }

    /**
     * Подготовка параметров CURL
     *
     * @return array      Параметры CURL
     */
    protected function getCurlOptions()
    {
        $options = array(
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADERFUNCTION => array($this, 'setResponseExpire'),
            CURLOPT_TIMEOUT => 10,
        );
        $userOptions = $this->getOption('CURL');
        if (is_array($userOptions)) {
            foreach ($userOptions as $id => $value) {
                $options[$id] = $value;
            }
        }

        return $options;
    }

    /**
     * Инициализация CURL. Установка параметров запроса.
     *
     * @param string    $path         Путь для запроса XML (может быть как локальным так и URL)
     * @param array     $postParams   POST-параметры запроса
     * @return resource   CURL
     */
    protected function initCurl($path, array $postParams = null)
    {
        $ch = curl_init();

        foreach ($this->getCurlOptions() as $id => $value) {
            curl_setopt($ch, $id, $value);
        }

        curl_setopt($ch, CURLOPT_URL, $path);

        if (is_array($postParams) && count($postParams)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postParams));
        }

        return $ch;
    }

    /**
     * Загрузка XML с адреса $_path. В случае ошибки позвращает false.
     * Ошибка получения XML от сервиса может быть из-за отсутствия ответа от сервера
     * либо из-за передачи некорректного XML
     *
     * @param string    $_path         Путь для запроса XML (может быть как локальным так и URL)
     * @return DOMDocument    Xml как DOM-объект, либо false в случае неудачи
     */
    protected function loadXmlFromMirror($path)
    {
        // Сброс заголовков о времени генерации данных и сроке годности
        $this->responseExpire = 0;

        try {
            $ch = $this->initCurl($path, $this->getRequestParams());
            $xmlStr = curl_exec($ch);
            curl_close($ch);
        } catch (ipoException $e) {
            $xmlStr = false;
        }

        if (false !== $xmlStr) {
            $xml = new DOMDocument();
            $xml->loadXML($xmlStr);
        } else {
            $xml = $xmlStr;
        }

        return $xml;
    }

    /**
     * Полчение названия темы из XML
     *
     * @param DOMDocument $_xml
     */
    public function getResponseThemeName()
    {
        if (is_null($this->responseThemeName)) {
            if ($this->xml instanceof DOMDocument) {
                $this->responseThemeName =  $this->xml->getElementsByTagName('RESPONSE')->item(0)->getAttribute('id');
            }

            if (!$this->responseThemeName) {
                throw ipoException::getException(ipoException::ERR_DOES_NOT_SET_RESPONSE_THEME);
            }
        }

        return $this->responseThemeName;
    }

    protected function setErrorXml(ipoException $e)
    {
        $this->responseThemeName = null;
        $this->xml = new DOMDocument;
        $this->xml->loadXML('<?xml version="1.0" encoding="utf-8"?><RESPONSE id="error"><ERROR><CODE>'.$e->getCode().'</CODE><DESCRIPTION>'.$e->getMessage().'</DESCRIPTION></ERROR></RESPONSE>');
    }

    /**
     * Загрузка XML из Интернет-сервиса
     *
     */
    protected function loadXml()
    {
        // Получение ответа от первого зеркала
        $this->xml = $this->loadXmlFromMirror($this->getPathToFirstMirror());

        // Если от первого зеркала получена ошибка, направляем запрос ко второму зеркалу
        if (false === $this->xml) {
            $this->xml = $this->loadXmlFromMirror($this->getPathToSecondMirror());
        }

        if (false == $this->xml) {
            throw ipoException::getException(ipoException::ERR_NO_RESPONSE);
        }
    }

    /**
     * Полчение XML из Интернет-Сервиса
     *
     * @return DOMDocument  XML как DOM-объект
     */
    public function getXml()
    {
        if (!($this->xml instanceof DOMDocument)) {
            try {
                $this->loadXml();
            } catch (ipoException $e) {
                $this->setErrorXml($e);
            }
        }

        return $this->xml;
    }
}