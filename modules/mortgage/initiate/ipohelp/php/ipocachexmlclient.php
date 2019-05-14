<?php
require_once(dirname(__FILE__).'/ipoxmlclient.php');

/**
 * К возможностям класса ipoXmlClient добавлено кэширование данных на стороне Клиента.
 * Перед запросом к Интернет-сервису проверяется наличие актуальных данных в кэше. Если в кэше данные есть,
 * то данные берутся из кэша и запрос к Интернет-сервису не производится. Если актуальных данных в кэше нет,
 * то после получения данных они кэшируются для повторных обращений.
 *
 * @author Александр Воробьев avorobiev@ipohelp.ru
 * @version 1.0
 * @package ipohelp_api
 */

class ipoCacheXmlClient extends ipoXmlClient
{
    protected
        /**
         * Таймстамп срока актуальности данных.
         */
        $cacheExpire
    ;

    /**
     * Добавление специфических для класса параметров
     *
     * @return []      Параметры класса по умолчанию
     */
    protected function getDefaultOptions()
    {
        return array_merge(
            parent::getDefaultOptions(),
            array(
                /* Путь к каталогу для кэширования. У процесса, выполняющего скрипт должны быть права
                rwx на каталог и его файлы. Путь должен заканчиваться слэшем */
                'cache_dir' => 'cache/',

                // Название файла, в котором хранятся данные об актуальности кэша
                'cache_actual_file' => 'cache_expire.txt',

                // Название каталога для хранения xml-кэша. Каталог будет создан внутри каталога cache_dir
                'cache_xml_dir' => 'xml/',

                /* Название тем, которые должны кэшироваться в виде XML (т.е. до XSLT-преобразования).
                Кэшировать XML обосновано для XML, на основании которых формируется несколько страниц*/
                'cached_xml_themes' => array('queryform', 'queryresult', 'programdescription', 'banklist', 'calccreditsizes', 'calcinstallments'),

                // Название темы с описанием возникшей ошибки
                'error_theme' => 'error'
            )
        );
    }
    /**
     * Рекурсивная функция. Создает строку для формирвоания ключа кэша.
     * Ключ формируется из параметров запроса, имеющих значения, и упорядоченных по возрастанию.
     *
     * @param mixed $param     Параметр (или массив с параметрами)
     * @param string $key      Название ключа
     * @return string   Строка для формирования ключа кэша
     */
    protected function getRequestParamsAsString($param, $key = null)
    {
        
        $ret = '';
        if (is_array($param) && ksort($param)) {
            foreach ($param as $id => $value) {
                $ret .= $this->getRequestParamsAsString($value, ($key ? $key.'['.$id.']' : $id));
            }
        } else if (!empty($key) && !empty($param)) {
            // Важно чтобы в хэше оказались только значащие для результата значения
            $ret = '&'.$key.'='.$param;
        }

        return $ret;                           
    }

    /**
     * Составление строки для формирования хэш-ключа для поиска данных в кэше
     *
     * @param string $themeName    Название темы
     * @return string   Строка для формирования хэша
     */
    protected function getCacheHashKey($themeName)
    {
        return $themeName.$this->getRequestParamsAsString($this->getRequestParams());
    }

    /**
     * Составление строки для формирования хэш-ключа для поика данных в кэше
     *
     * @param string $themeName    Название темы
     * @return string   Строка для формирования хэша
     */
    protected function getXmlCacheHashKey($themeName)
    {
        return $this->getCacheHashKey($themeName);
    }

    /**
     * Формирование пути к каталогу с кэшем
     *
     * @param string $type           Тип кэша. Ожидаемые значения xml|html
     * @return string Путь к каталогу с кэшем
     */
    protected function getCacheDir($type)
    {
        return $this->getRequiredOption('cache_dir').$this->getRequiredOption('cache_'.$type.'_dir');
    }

    /**
     * Формирование пути к данным в кэше
     *
     * @param string $type         Тип кэша. Допустимые значения xml|html
     * @param string $themeName    Название темы
     * @return string(32)     Путь к кэшу
     */
    protected function getCachePath($type, $themeName)
    {
    $fCacheHash = 'get'.ucfirst($type).'CacheHashKey';
    return $this->getCacheDir($type).md5($this->$fCacheHash($themeName)).'.'.$type;
    }

    /**
     * Определение пути к файлу с данными об актуально кэша
     * @return string Путь к файлу с данными об актуальности кэша
     */
    protected function getCacheExpirePath()
    {
        return $this->getOption('cache_dir').$this->getOption('cache_actual_file');
    }

    /**
     * Получение строки с данными об актуальности кэша из файла
     *
     * @return string   Строка с данными об актуальности кэша
     */
    protected function getCacheExpireFromFile()
    {
        return @file_get_contents($this->getCacheExpirePath());
    }

    /**
     * Получение данных об актуальности кэша
     *
     * @return array
     */
    protected function getCacheExpire()
    {
        if (is_null($this->cacheExpire)) {
            $cacheExpire = $this->getCacheExpireFromFile();
            $this->cacheExpire = (is_numeric($cacheExpire) && $cacheExpire>0) ? $cacheExpire : 0;
        }

        return $this->cacheExpire;
    }

    /**
     * Рекурсивное удаление каталога и вложенных в него подкаталогов и файлов.
     * Используется для удаления кэша, потерявшего актуальность.
     *
     * @param string $dir   Путь к каталогу, который должен быть удален
     */
    protected function delTree($dir)
    {
        foreach(glob($dir.'*', GLOB_MARK) as $file) {
            if (DIRECTORY_SEPARATOR == substr($file, -1)) {
                $this->delTree($file);
                @rmdir($file);
            } else {
                @unlink($file);
            }
        }
    }

    /**
     * Сброс кэша
     *
     */
    protected function clearCache()
    {
        $this->delTree($this->getCacheDir('xml'));
    }

    /**
     * Сохранение новой точки актуальности кэша
     *
     * @param array $cacheExpire  timestamp - время актуальности
     * @return    Количество записанных байт, либо false в случае сбоя записи
     */
    protected function saveCacheExpire($cacheExpire)
    {
        $this->cacheExpire = $cacheExpire;
        return file_put_contents($this->getCacheExpirePath(), $this->cacheExpire);
    }

    /**
     * Обновление данных точки актуальности кэша
     *
     * @param array $responseExpire   timestamp - время актуальности полученного ответа
     */
    protected function updateCacheExpire($responseExpire)
    {
        $cacheExpire = $this->getCacheExpire();

        // Если изменилась дата генерации данных, то
        if ($responseExpire != $cacheExpire) {
            // обнуляем кэш
            $this->clearCache();

            // обновляем точку актуальности
            $this->saveCacheExpire($responseExpire);
        }
    }

    /**
     * Проверка актуальности кэша
     *
     * @return boolean    true если кэш актуальный, либо false
     */
    protected function checkCacheExpire()
    {
        return $this->getCacheExpire() >= time();
    }

    /**
     * Получение данных из кэша
     *
     * @param string $type   Тип кэша. Допустимые значения xml|html
     * @param string $themeName    Название темы
     * @return string   Строка, содержащая данные из кэша
     */
    protected function loadFromCache($type, $themeName)
    {
        $ret = false;
        if ($this->checkCacheExpire()) {
            $cachePath = $this->getCachePath($type, $themeName);

            if (is_file($cachePath)) {
                $ret = @file_get_contents($cachePath);
            }
        }

        return $ret;
    }

    /**
     * Получение XML из кэша
     *
     * @return mixed    XML в формате DOMDocument либо false
     */
    protected function loadXmlFromCache()
    {
        $xml = $this->loadFromCache('xml', $this->getRequestThemeName());

        $ret = false;
        if (false !== $xml) {
            $ret = new DOMDocument();
            $ret->loadXML($xml);
        }

        return $ret;
    }

    /**
     * Сохранение текста в кэше
     *
     * @param string $path       Путь к файлу для сохранения кэша
     * @param string $string     Текст для сохранения в кэше
     * @return int      Количество сохраненных в файл байт, либо false
     */
    protected function saveCacheToFile($path, $string)
    {
        $ret = false;

        /**
         * Запись в кэш выполняется только если есть информация о времени актуальности кэша.
         * Иначе кэшировать данные бессмысленно.
         */
        if ($this->cacheExpire && !empty($string)) {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $ret = file_put_contents($path, $string);
        }

        return $ret;
    }

    /**
     * Кэширование XML
     *
     * @return int - число байт при сохранении в кэш или false
     */
    protected function cacheXML()
    {
        $ret = false;

        // В кэше сохраняются только темы, заданные в опции cached_xml_themes
        if (($this->xml instanceof DOMDocument) && in_array($this->getResponseThemeName(), $this->getOption('cached_xml_themes'))) {
            $this->updateCacheExpire($this->getResponseExpire());

            /**
             * Для формирвоания хэша кэша используется getRequestThemeName, поскольку для темы vehicle
             * request будет код объявления, а в response 'vehicle'. Чтобы при запросе данных в кэша они
             * находились, используем для кэширования всегда данные из getRequestThemeName.
             */
            $ret = $this->saveCacheToFile($this->getCachePath('xml', $this->getRequestThemeName()), $this->xml->saveXML());
        }

        return $ret;
    }

    /**
     * Загрузка XML из кэша, либо, при отсутствии в кэше, из Интернет-Сервиса
     *
     * @return DOMDocument  XML как DOM-объект
     */
    protected function loadXml()
    {
        try {
            if (!($this->xml = $this->loadXmlFromCache())) {
                // Загрузка из Интернет-сервиса
                parent::loadXml();

                // Кэширование полученного Xml
                $this->cacheXML();
            }
        } catch (ipoException $e) {
            $this->setErrorXml($e);
        }
    }
}