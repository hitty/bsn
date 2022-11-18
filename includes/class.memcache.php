<?php
/**
 * Проксирование мемкэша
 * (для устранения Dog pile effect и снижения нагрузки на сервер)
 */

define('MC_LOCK_WAITING',5);                    // время ожидания освобождения заблокированной записи (сек)
define('MC_LOCK_CHECK_INTERVAL',0.1);           // интервал проверки освобождения блокировки (сек)
define('MC_LOCK_SUFFIX','_dc_lock_state_flag'); // суффикс для записи о блокировке ключа


class MCache extends Memcache{
    public $connected = false;                  // признак активности соединения

    public function __construct($host=null, $port=null){
        if(!empty($host)) $this->connectC($host,$port);
    }

    public function connectC($host, $port){
        return $this->connected = parent::connect($host,$port,1);
    }

    /**
     * Блокировка ключа (для извещения о ходе подготовки данных)
     * если заведомо известно, что идет формирование данных, которые
     * будут скоро записаны в кэш, есть смысл в начале расчета установить
     * блокировку на ключ, а после записи в кэш данных, снять блокировку с ключа
     * @param string ключ элемента
     * @param integer время сохранения блокировки
     * @return boolean
     */
    public function lock($key = '', $timeout = 3600){
        if (!$this->connected) return FALSE;
        if (empty($key)) return FALSE;
        return parent::set($key.MC_LOCK_SUFFIX, 1, FALSE, $timeout);
    }

    /**
     * Снятие блокировки с ключа (для извещения об окончании подготовки данных)
     * если заведомо известно, что идет формирование данных, которые
     * будут скоро записаны в кэш, есть смысл в начале расчета установить
     * блокировку на ключ, а после записи в кэш данных, снять блокировку с ключа
     * @param string ключ элемента
     * @return boolean
     */
    public function unlock($key = ''){
        if (!$this->connected) return FALSE;
        if (empty($key)) return FALSE;
        return parent::delete($key.MC_LOCK_SUFFIX);
    }

    /**
     * Mirror for $memcache->get() method
     * получение данных
     */
    public function gets($key = '', &$param1='', &$param2=''){
        if (!$this->connected) return FALSE;
        if (empty($key)) return FALSE;
        $data = parent::get($key);
        if($data === FALSE){
            // если данных еще нет, смотрим, заблокирован ли ключ
            $counter = MC_LOCK_WAITING/MC_LOCK_CHECK_INTERVAL;
            while($counter>0 && parent::get($key.MC_LOCK_SUFFIX)!==FALSE) {
                // пытаемся подождать разблокирования ключа
                usleep(1000000*MC_LOCK_CHECK_INTERVAL);
                $counter--;
            }
            // если ключ был разблокирован, получаем новое значение
            if($counter>0 && $counter<MC_LOCK_WAITING/MC_LOCK_CHECK_INTERVAL) {
                $data = parent::get($key);
            }
        }
        if($data !== FALSE && $this->_is_valid_cache($data)) {
            if(!isset($data['_dc_cache'])) $data['_dc_cache'] = NULL;
            //check lifetime
            if (time() > $data['_dc_life_end']) {
                //expired, save the same for a longer time for other connections
                $this->sets($key, $data['_dc_cache'], FALSE, $data['_dc_cache_time']);
                return FALSE;
            } else {
                return $data['_dc_cache'];
            }
        }
        // если нет данных и при этом...
        // - либо нет блокировки ключа
        // - либо есть блокировка ключа, но не дождлись его разблокирования
        // или данные не корректные
        return FALSE;
    }

    /**
     * Mirror for $memcache->set() method
     * запись значения в кэш
     * @param string ключ
     * @param mixed данные
     * @param mixed флаг компресcии (FALSE или константа MEMCACHE_COMPRESSED)
     * @param integer время кэширования (в секундах, максимум до 15 дней)
     */
    public function sets($key = '', $data = '', $flag = FALSE, $timeout = 3600){
        if (!$this->connected) return FALSE;
        if(empty($key)) return FALSE;
        // Maximum timeout = 15 days - 1 second
        if ((int)$timeout == 0 || (int)$timeout > 1295999) $timeout = 1295999;
        return $this->_set($key, $data, $flag, $timeout * 2);
    }

    /**
     * Обёртка и сохранение данных с рабочими дескрипторами
     */
    private function _set($key = '', $data = '', $flag = FALSE, $timeout = 3600){
        $cache = array('_dc_cache' => $data, '_dc_life_end' => time() + $timeout, '_dc_cache_time' => $timeout);
        return parent::set($key, $cache, $flag, $timeout);
    }

    // Maybe we have pure Memcache data, not our array structure
    private function _is_valid_cache($value){
        return (is_array($value) &&
            isset($value['_dc_life_end']) &&
            isset($value['_dc_cache_time']) &&
            !empty($value['_dc_life_end']) &&
            !empty($value['_dc_cache_time'])
        ) ? TRUE : FALSE;
    }
}
?>