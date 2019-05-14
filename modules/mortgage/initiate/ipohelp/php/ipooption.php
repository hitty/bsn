<?php
require_once('ipoexception.php');

/**
 * Абстрактный класс обеспечивающий работу с опциями
 *
 * @author Александр Воробьев avorobiev@ipohelp.ru
 * @version 1.0
 * @package ipohelp_api
 */

abstract class ipoOption
{
    // Параметры по умолчанию задаются в методе getDefaultOptions() на наследниках
    private $options = [];

    /**
     * Конструктор
     *
     * @param array $options   Параметры класса, перекрывающие параметры заданные по умолчанию
     */
    public function __construct(array $options = [])
    {
        $this->setOptions(array_merge($this->getDefaultOptions(), $options));
    }

    /**
     * Метод должен возвращать массив с параметрами по умолчанию
     *
     * @return array Массив с параметрами по умолчанию
     * @abstract
     */
    abstract protected function getDefaultOptions();

    /**
     * Получение значения параметра по названию
     *
     * @param string $name   Название параметра
     * @return mixed    Значение параметра
     */
    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    public function getRequiredOption($name)
    {
        if (!isset($this->options[$name]) || !$this->options[$name]) {
            throw ipoException::getException(ipoException::ERR_DOES_NOT_SET_REQUIRED_OPTION, $name);
        }

        return $this->options[$name];
    }

    /**
     * Получение массив со всеми параметрами класса
     *
     * @return array    Параметры класса
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Установка параметра класcа
     *
     * @param string $name         Название параметра
     * @param mixed $value         Значение параметра
     * @return true
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return true;
    }

    /**
     * Добавление/установка нескольких параметров класса
     *
     * @param array $options     Массив с добавляемыми параметрами
     * @return true
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }

        return true;
    }
}