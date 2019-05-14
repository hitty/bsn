<?php
/**
 * Исключения, используемые при обработке запросов к Интернет-сервису IPOhelp.ru
 *
 * @author Александр Воробьев avorobiev@ipohelp.ru
 * @version 1.0
 * @package ipohelp_api
 */

class ipoException extends Exception
{
    // Коды ошибок
    const
        ERR_404 = 404,
        ERR_WRONG_REQUEST_THEME = 1010,
        ERR_DOES_NOT_SET_REQUEST_THEME = 1020,
        ERR_DOES_NOT_SET_PARTNER_ID = 1030,
        ERR_DOES_NOT_SET_MIRROR = 1040,
        ERR_DOES_NOT_SET_RESPONSE_THEME = 1050,
        ERR_NO_RESPONSE = 1060,
        ERR_DOES_NOT_SET_REQUIRED_OPTION = 1070,
        ERR_OTHER = 1100
    ;

    // Сообщения об ошибках
    static public
        $err_messages = array(
            self::ERR_404 => 'Запрашиваемые данные не существуют.',
            self::ERR_WRONG_REQUEST_THEME => 'Интернет-сервис не поддерживает запрос темы "%s".',
            self::ERR_DOES_NOT_SET_REQUEST_THEME => 'Не задана тема для запроса к Интернет-сервису.',
            self::ERR_DOES_NOT_SET_PARTNER_ID => 'Не задан код партнера.',
            self::ERR_DOES_NOT_SET_MIRROR => 'Не задан путь до зеркалa "%s" Интернет-Сервиса.',
            self::ERR_DOES_NOT_SET_RESPONSE_THEME => 'Не удалось определить тему ответа.',
            self::ERR_NO_RESPONSE => 'Интернет-сервис не отвечает.',
            self::ERR_DOES_NOT_SET_REQUIRED_OPTION => 'Не задан обязательный параметр "%s".',
            self::ERR_OTHER  => 'Произошел сбой в работе.'
        );

    /**
     * Генерация исключения
     *
     * @param int $_code      Код исключения (см. константы класса)
     * @param mixed $_param   Параметры сообщения об исключении
     * @return ipoException   Исключение
     */
    static public function getException($code, $param = null)
    {
        return false;
    }
}