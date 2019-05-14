<?php
		
require('modules/mortgage/initiate/ipohelp/php/ipocachexmlclient.php');

class bsnClient extends ipoCacheXmlClient
{
    static protected
        $initialOptions          // Параметры инициализации класса
    ;

    protected
        $contentName,
        $xslParams = []
    ;

    protected function getDefaultOptions()
    {
        return array_merge(
            parent::getDefaultOptions(),
            array(
                // Адрес домена, в рамках которого развернут сервис
                'url_domen'         => '',
                'url_mortgage'      => '{domen}/estate/mortgage/',
                'url_spbprograms'   => '{domen}/estate/mortgage/spb/',
                'url_programs'      => '{domen}/estate/mortgage/programs/',
                'url_program'       => '{domen}/estate/mortgage/program/',
                'url_bannks'        => '{domen}/estate/mortgage/banks/',
                'url_search'        => '{domen}/estate/mortgage/search/',

                // Префикс для отделения параметров сервиса от прочих параметров страницы (например куки)
                'prefix'        => 'ipo_',

                //Разделитель контента от тайтла в html-кэше
                'delimiter'     => PHP_EOL.'_____ipohelp.ru delimiter_____'.PHP_EOL,

                // Название тем, которые должны кэшироваться с виде HTML
                'cached_html_themes' => array('queryform', 'queryresult', 'programdescription', 'banklist', 'spbprograms'),
            )
        );
    }

    /**
     * Добавление к хэшу xsl-параметров, чтобы кэш страницы 1 отличался от кэша
     * страницы №2.
     *
     * @param string $themeName
     * @return string
     */
    protected function getHtmlCacheHashKey($themeName)
    {
        return parent::getHtmlCacheHashKey($themeName).$this->getRequestParamsAsString($this->xslParams);
    }

    /**
     * Добавление в html-кэш тайтла
     *
     */
    protected function getHtml4Cache(DOMDocument $html)
    {
        return parent::getHtml4Cache($html)
                .$this->getRequiredOption('delimiter')
                .$this->getContentName()
        ;
    }

    /**
     * Получение из кэша тайтла и контента. Тайтл сохраняется в $this->contentName
     *
     * @param string $type   Тип кэша. Допустимые значения xml|html
     * @param string $themeName    Название темы
     * @return string   Строка, содержащая данные из кэша
     */
    protected function loadFromCache($type, $themeName)
    {
        $str = parent::loadFromCache($type, $themeName);

        if (!empty($str) && ('html' == $type)) {
            $arr = explode($this->getRequiredOption('delimiter'), $str);

            if (count($arr) > 1) {
                $str = $arr[0];
                $this->contentName = $arr[1];
            }
        }

        return $str;
    }

    /**
     * Добавление URL домена, в рамках которого развернут раздел
     *
     * @param string $value      Значение, в котором необходимо выполнить замену
     * @param string $replace    Строка, на которую должен быть заменен служебный ключ {domen}
     * @return string
     */
    protected function addDomen($value, $replace = null)
    {
        if (is_null($replace)) {
            $replace = $this->getOption('url_domen');
        }

        return false !== strpos($value, '{domen}')
              ? str_replace('{domen}', $replace, $value)
              : $value
        ;
    }

    protected function getContentType()
    {
        return $this->getResponseThemeName();
    }

    protected function getPageNumForContentName()
    {
        return !empty($this->xslParams['page']) && $this->xslParams['page'] > 1
                    ? ', стр. '.$this->xslParams['page']
                    : ''
        ;
    }

    /**
     * Формирование названий, используемых для title и bread-crumbs
     *
     * @return string        Сформированной название
     */
    public  function getContentName()
    {
        if (is_null($this->contentName)) {
            switch ($this->getContentType()) {
                case 'queryform':
                    $this->contentName = 'подбор ипотечной программы';
                    break;
                case 'banklist':
                    $this->contentName = 'список ипотечных банков'.$this->getPageNumForContentName();
                    break;
                case 'queryresult':
                    $this->contentName = 'ипотечные программы'.$this->getPageNumForContentName();
                    break;
                case 'spbprograms':
                    $this->contentName = 'ипотечные программы Санкт-Петербурга'.$this->getPageNumForContentName();
                    break;
                case 'programdescription':
                    $this->contentName = sprintf(' ипотечная программа %s', $this->getXml()->getElementsByTagName('NAME')->item(0)->nodeValue);
                    break;
                default:
                    $this->contentName = 'ошибка';
            }
        }

        return $this->contentName;
    }

    /**
     * Установка заголовка ответа 404 или 500 в зависимости от типа ошибки
     */
    protected function setErrorHeader()
    {
        $errorCode = intval($this->xml->getElementsByTagName('CODE')->item(0)->nodeValue);
        if (!headers_sent()) {
            if (in_array($errorCode, array(404, ipoException::ERR_DOES_NOT_SET_REQUEST_THEME))) {
                header("HTTP/1.0 404 Not Found");
            } else {
                header("HTTP/1.0 500 Internal Server Error");
            }
        }
    }

 

 

    /**
     * Метод перекрыт для возвращения заголовков об ошибке
     *
     * @param ipoException $e  Исключение
     */
    protected function setErrorXml(ipoException $e)
    {
        parent::setErrorXml($e);
        $this->setErrorHeader();
    }

    /**
     * Проверка запрашиваемого номера страницы на максимальное значение
     *
     * @return bool
     * @throws ipoException
     */
    protected function validatePageNumber(DOMNodeList $list)
    {
        return true;
    }


    /**
     * Получение поисковых параметров из $_GET
     *
     * @return array
     */
    public function setRequestParams($params = null)
    {
        try {
            // Если значения явно не заданы, то получаем их из $_REQUEST...
            if (is_null($params)) {
                $prefix = $this->getRequiredOption('prefix');
                $offset = strlen($prefix);

                $params = [];
                foreach ($_REQUEST as $name => $value) {
                    if (0 === strpos($name, $prefix)) {
                        $params[substr($name, $offset)] = $value;
                    }
                }
            }

            // ... и добавляем к ним значения по умолчанию
            if (in_array($this->getRequestThemeName(), array('queryform', 'banklist', 'queryresult'))
                && empty($params['regionid'])
            ) {
                $params['regionid'] = 30;
            }


            return parent::setRequestParams($params);
        } catch (ipoException $e) {
            $this->setErrorXml($e);
        }
    }

    /**
     * Установка параметров инициализации класса
     *
     * @param array $_options
     */
    static public function setInitialOptions(array $_options)
    {
        self::$initialOptions = $_options;
    }


    /**
     * Создание экземпляра класса
     *
     * @return bsnClient
     */
    static public function createInstance()
    {
        return new self(self::$initialOptions);
    }
}