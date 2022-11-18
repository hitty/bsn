<?php
if( !class_exists('Host') ) include('includes/class.host.php');
class mysqli_db extends mysqli{
    public $last_query = "";
    public $querylog = array();
    private $errors_filename = 'errors_query.log';
    private $cron_errors_filename = 'cron_errors_query.log';
    private $users_logs_filename = 'users_logs_query.log';

    /**
     * Экранирование зеачений для последующей записи в БД
     * @param mixed значение для экранирования
     * @return mixed экранированное значение
     */
    public function quoted( $value ) {
        if($value===null) $value = "NULL";
        elseif(is_array($value)) $value = "''";
        elseif(!Validate::Numeric($value) || (Validate::Numeric($value) && preg_match('/^0/',$value))) $value = "'" . parent::real_escape_string( $value ) . "'";
        return $value;
    }
    /**
     * Подстановка в строку запроса указанных параметров с экранированием
     * @param string строка запроса, где в местах подстановок прописаны знаки ?
     * @param array массив параметров для подстановки в строку запросов вместо знаков ?
     * @return mixed сформированный запрос (или false в случае неудачной подстановки)
     */
    private function query_prepare( $query, $args ){
        $aq = preg_split("/<\?>|\?/msi", $query);
        if( !is_array( $aq )  ) return '';
        if(count($aq) != count($args)+1) return false;
        $query = '';
        for ($i = 0; $i < count($args); $i++) {
            $query .= array_shift($aq).$this->quoted($args[$i]);
        }
        $query .= array_shift($aq);
        return $query;
    }

    /**
     * Подстановка в строку запроса указанных параметров с экранированием
     * @param string строка запроса, где в местах подстановок прописаны знаки ?
     * @param ... произвольное кол-во параметров для подстановки (должно быть меньше или равно кол-ву знаков ? в строке запроса)
     * @return mixed обработанный запрос (или false в случае неудачной подстановки)
     */
    public function query_add_parameters( $query ){
        $arg_list = func_get_args();
        $query = array_shift($arg_list);
        $aq = preg_split("/<\?>|\?/msi", $query);
        $count = count($arg_list);
        if(count($aq) < $count+1) $count = count($aq)-1;
        $query = '';
        for ($i = 0; $i < $count; $i++) {
            $query .= array_shift($aq).$this->quoted($arg_list[$i]);
        }
        $query .= implode('?', $aq);
        return $query;
    }


    /**
     * Выполнение запроса с параметрами
     * @param string строка запроса со знаками ? в местах для подстановки параметров
     * @param ... произвольное кол-во параметров для подстановки (должно быть равно кол-ву знаков ? в строке запроса)
     * @return mixed результат выполнения запроса
     */
    public function querys( $query = '', $resultmode = NULL ){
        global $query_not_log, $auth, $sys_tables;
        $arg_list = func_get_args();
        $query = array_shift($arg_list);
        if(!empty($arg_list)){
            $query = $this->query_prepare($query, $arg_list);
            if($query===false) return false;
        }
        $e = new Exception;
        $tracestack = explode("\n", $e->getTraceAsString());
        $this->last_query = $query;
        $query_time = microtime(true);
        if ( empty( $query ) ) return false;
        $result = parent::query($query);
        if(!empty($_GET['path']) && strstr($_GET['path'],'/manage/') && preg_match('/UPDATE|DELETE|INSERT/msiU', $query)  && !preg_match('/last_enter|popup_notification = 1/msiU', $query) && !empty($auth->id)){
            $date_now = new DateTime();
            $log_datetime = $date_now->format("Y-m-d H:i:s");
            $log_query = "INSERT INTO service.users_logs SET id_user = ".$auth->id.", `query` = '".$this->real_escape_string($query)."', uri = '".$_GET['path']."', datetime = '".$log_datetime."'";
            $this->saveUsersLogsData($log_query);
        }
        if(empty($query_not_log)) $this->querylog[] = array('sql'=>$query , 'time'=>round(microtime(true) - $query_time, 4), 'stack'=>$tracestack);
        if(!empty($this->error)){
            if(DEBUG_MODE){
                echo 'Error: '.$this->error.'; <br /><br />
                      Query: '.$query.'<br />---------------------------------------<br />';
                $cron = ( strstr($this->error, 'Duplicate entry') == '' && ( (!empty($_SERVER['PWD']) && strstr($_SERVER['PWD'], '/public_html/cron') != '' ) || ( !empty($_SERVER['REQUEST_URI']) && strstr($_SERVER['REQUEST_URI'], '/cron') != '' ) || ( !empty($_SERVER['PHP_SELF']) && strstr($_SERVER['PHP_SELF'], '/cron/') != '' )));
                $this->saveErrorsData($query, $tracestack, false, $cron);
            } else {
                //определяем, cron или не
                $cron = ( strstr($this->error, 'Duplicate entry') == '' && ( (!empty($_SERVER['PWD']) && strstr($_SERVER['PWD'], '/public_html/cron') != '' ) || ( !empty($_SERVER['REQUEST_URI']) && strstr($_SERVER['REQUEST_URI'], '/cron') != '' ) || ( !empty($_SERVER['PHP_SELF']) && strstr($_SERVER['PHP_SELF'], '/cron/') != '' )));
                if(!$cron)
                    array_unshift($tracestack,
                        'url: '.$_SERVER["SCRIPT_FILENAME"]."<br />"
                    );
                else
                    array_unshift($tracestack,
                        'url: http://'.Host::$host.'/'.Host::$requested_uri."<br />"
                        ."Ref: ".Host::getRefererURL()."<br />"
                        ."IP: ".Host::getUserIp()."<br />"
                        ."User Agent: ".$_SERVER['HTTP_USER_AGENT']."<br />"
                    );

                $this->saveErrorsData($query, $tracestack, false, $cron);
            }

        }
        $user_log = false;
        return $result;
    }

    /**
     * возвращает строку выборки (первую, если их несколько)
     * @param string $query строка запроса
     * @param set of values
     * @return array|false результат выборки
     */
    public function fetch($query){
        global $this_page;
        // просмотр дополнительных аргументов
        $numargs = func_num_args();
        if(!$numargs) return false;
        $arg_list = func_get_args();
        $query = array_shift($arg_list);
        if(!empty($arg_list)){
            $query = $this->query_prepare($query, $arg_list);
            if($query===false) return false;
        }
        $result = $this->querys( $query );
        if( !$result ) return false;
        $arr = false;
        if( !empty($result->num_rows) && $result->num_rows>0 ){
            $arr = $result->fetch_assoc();
        }
        if(!is_array($arr)) $arr = false;
        return $arr;
    }


    /**
     * возвращает массив строк выборки (если заан ключ, то именованный по значениям этого ключевого поля)
     * @param string строка запроса
     * @param mixed name of primary field or false
     * @param set of values
     * @return array|false результат выборки
     */
    public function fetchall($query, $byPrimary = false){
        global $this_page;
        $numargs = func_num_args();
        if(!$numargs) return false;
        $arg_list = func_get_args();
        $query = array_shift($arg_list);
        if(!empty($arg_list)) @array_shift($arg_list);
        if(!empty($arg_list)){
            $query = $this->query_prepare($query, $arg_list);
            if($query===false) return false;
        }
        $result = $this->querys( $query );
        if( !$result ) return array();
        $r = array();
        while($a = $result->fetch_assoc()) {
            if( !empty($byPrimary) && !empty($a[$byPrimary]) && (is_numeric($a[$byPrimary]) || is_string($a[$byPrimary]))) {
                $r[$a[$byPrimary]] = $a;
            } else {
                $r[] = $a;
            }
        }
        mysqli_free_result($result);
        return $r;
    }

    /**
     * Получение описания таблицы
     * @param string $tablename
     * @return array
     */
    public function getTableInfo( $tablename ) {
        $query = "DESC " . $tablename;
        return $this->fetchall( $query, "Field" );
    }

    /**
     * Создание новой пустой записи по описанию таблицы
     * @param string таблица
     * @return array запись
     */
    public function prepareNewRecord($tablename){
        $structure = $this->getTableInfo($tablename);
        $array = array();
        foreach($structure as $field=>$desc){
            switch($desc['Type']){
                case 'datetime':
                    $array[$field] = date('Y-m-d H:i:s');
                    break;
                case 'date':
                    $array[$field] = date('Y-m-d');
                    break;
                case 'time':
                    $array[$field] = date('H:i:s');
                    break;
                case 'timestamp':
                    $array[$field] = time();
                    break;
                case 'year':
                    $array[$field] = date('Y');
                    break;
                case 'text':
                    $array[$field] = '';
                    break;
                default:
                    if($desc['Default']!==null) $array[$field] = $desc['Default'];
                    elseif($desc["Key"]!="PRI") $array[$field] = null;
            }
        }
        return $array;
    }

    /**
     * Обновление строки из ассоциированного массива
     * @param string таблица для обновления
     * @param array ассоциированный массив с данными (ключевое поле должно присутствовать)
     * @param string название (ключ) ключевого поля
     * @param bool обновлять значения, если они NULL
     * @return bool
     */
    public function updateFromArray( $tablename, $array, $keyfield, $update_null=false ){
        $fields = array();
        $tablemap = $this->getTableInfo($tablename);
        foreach($array as $key=>$value){
            if( !in_array($key,array_keys($tablemap)) ) continue;
            if( $key==$keyfield || ($value===null && !$update_null) ) continue;
            $fields[] = "`$key` = ".$this->quoted($value);
            $h = $this->quoted($value);
        }
        $query = "UPDATE $tablename SET ".implode(', ',$fields)." WHERE `$keyfield`=".$this->quoted($array[$keyfield]);
        return $this->querys($query);
    }
    /**
     * Добавление строки из ассоциированного массива
     * @param string таблица для добавления
     * @param array ассоциированный массив с данными
     * @param string название (ключ) ключевого поля (не добавляется если указано)
     * @param bool добавлять значения, если они NULL
     * @param bool использовать INSERT IGNORE
     * @return bool
     */
    public function insertFromArray( $tablename, $array, $keyfield=false, $update_null=false, $use_insert_ignore=false ){
        $fields = $values = array();
        $tablemap = $this->getTableInfo($tablename);
        foreach($array as $key=>$value){
            if( !in_array($key, array_keys($tablemap)) ) continue;
            if($key==$keyfield || ($value===null && !$update_null)) continue;
            $fields[] = "`$key`";
            $values[] = $this->quoted($value);
        }
        $query = "INSERT ".( !empty( $use_insert_ignore ) ? "IGNORE" : "" )." INTO $tablename (".implode(',',$fields).") VALUES (".implode(',',$values).")";
        return $this->querys($query);
    }
    /**
     * возвращает неверные запросы
     * @return array
     */
    public function loadErrorsData($path = false,$cron = false){
        $filename = (!empty($cron) ? $this->cron_errors_filename : $this->errors_filename);

        if(!empty($cron)) $filename = ROOT_PATH."/".$this->cron_errors_filename;
        else $filename = empty($path) ? Host::getRealPath($filename) : $path.'/'.$filename;

        if(file_exists($filename)) return unserialize(file_get_contents($filename));
        else return false;
    }
    /**
     * сохраняет неверные запросы
     * @return array
     */
    public function saveErrorsData($query, $tracestack, $array = false, $cron = false){
        if(empty($array)){
            $filename = $this->loadErrorsData(false, $cron);
            if(!is_array($filename)) $filename = array();
            $array = array_merge($filename, array($query => $tracestack));
            array_unique($array, SORT_REGULAR);
        }
        if(!empty($cron)) file_put_contents(ROOT_PATH."/".$this->cron_errors_filename, serialize($array));
        else file_put_contents(Host::getRealPath($this->errors_filename), serialize($array));
    }
    /**
     * очистка файла
     */
    public function clearErrorsData($path = false){
        if(!empty($cron)) $filename = empty($path) ? ROOT_PATH."/".$this->cron_errors_filename : $path.'/'.$this->cron_errors_filename;
        else{
            $filename = empty($path) ? Host::getRealPath($this->errors_filename) : $path.'/'.$this->errors_filename;
            file_put_contents($filename, '');
            $filename = empty($path) ? ROOT_PATH."/".$this->cron_errors_filename : $path.'/'.$this->cron_errors_filename;
        }
        file_put_contents($filename, '');
    }
    /**
     * возвращает список запросов пользователей
     * @return array
     */
    public function loadUsersLogsData($path = false){
        $filename = empty($path) ? Host::getRealPath($this->users_logs_filename) : $path.'/'.$this->users_logs_filename;
        if(file_exists($filename)) return unserialize(file_get_contents($filename));
    }
    /**
     * сохраняет список запросов пользователей
     * @return array
     */
    public function saveUsersLogsData($query, $array = false){
        if(empty($array)){
            $filename = $this->loadUsersLogsData();
            if(!is_array($filename)) $filename = array();
            $array = array_merge($filename, array($query));
            array_unique($array, SORT_REGULAR);
        }
        file_put_contents(Host::getRealPath($this->users_logs_filename), serialize($array));
    }
    /**
     * очистка файла
     */
    public function clearUsersLogsData($path = false){
        $filename = empty($path) ? Host::getRealPath($this->users_logs_filename) : $path.'/'.$this->users_logs_filename;
        file_put_contents($filename, '');
    }
}
?>