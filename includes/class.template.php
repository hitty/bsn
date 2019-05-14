<?php
/**
* ----------------------------------------------------------------------------------------------------------------------
* Class Templates
* ----------------------------------------------------------------------------------------------------------------------
*/

class Template {
    private $templates_folder = "templates";
    public $template = '';
    private $recursive_counter = 0;

    public function __construct($template_filename, $path='', $counter = 0, $content = false) {
        $this->recursive_counter = $counter;
        $this->template = $this->Load( $template_filename , $path, $content);
    }
    
    /**
    * Обработка шаблона
    * @return string HTML
    */
    public function Processing(){
        if($this->recursive_counter>10) {
            $this->template = "ERROR: recursive calls overflow in templates";
        }
        $this->Combine();
        $phptemplate_path = FileCache::GetCachedPath($this->template);
        if( empty( $phptemplate_path ) ) {
            $php_template = $this->CreatePHP($this->template);
            $phptemplate_path = FileCache::Write($this->template, $php_template);
        }
        $html_contents = $this->CreateHTML($phptemplate_path);
        return $html_contents;
    }
    
    /**
    * Compress the text by removing duplicate blank characters
    * @param string $tpl tpl contents
    * @return string
    */
    public function CompressTPL($tpl){
        if(TPL_COMPACT==1){
            $tpl = preg_replace('|[\s\t\n\r]+|umsi', ' ', $tpl);
            $tpl = preg_replace('|>[\s\t\n\r]+<|umsi', "><", $tpl);
        }
        return $tpl;
    }

    /**
     * Creates (and show) html from php-notation file
     * @param string filename with template php-notation
     * @param boolean show in browser
     * @return string content of HTML
     */
    public function CreateHTML($php_file_path) {
        // подготовка переданных параметров
        $requesred_parameters_for_page = Request::GetParameters();
        foreach( $requesred_parameters_for_page as $rpp_key => $rpp_value ) {
            $$rpp_key = $rpp_value;
        }
        // включение буфферизации
        ob_start();
        require($php_file_path);
        $buffer = ob_get_contents();
        ob_end_clean();
        // раскрытие внутренних вызовов (блоки контента)
        $mcount = preg_match_all( '!\{(block|page)\s+([^}\s]+)\s*\}!i', $buffer, $matches, PREG_SET_ORDER);
        if($mcount){
            $backup_environment_parameters = Request::GetParameters(); // сохраняем все текущие параметры (могут быть перезаписаны внутри новой страницы)
            foreach($matches as $match){
                $pg = new Page($match[2]);
                $cont = $pg->Render(true);
                $buffer = str_replace( $match[0], $cont, $buffer );
            }
            Response::SetParametersFromArray($backup_environment_parameters); // восстанавливаем текущие параметры
        }
        return $buffer;
    }

    /**
     * @desc Load template file for working with it
     * @param string template filename (with path)
     * @return string contents of template
     * @return string template content
     */
    public function Load($filename, $path, $content = false) {
        if($content) return $content;
        else{
            if(Config::get('win_os')) $filename = trim($filename,'/');
            $path = trim($path,'/');
            if(file_exists(Config::Get('site/root_path').'/'.$filename)) return file_get_contents(Config::Get('site/root_path').'/'.$filename);
            elseif(file_exists(Config::Get('site/root_path').'/'.$path.'/templates/'.$filename)) return file_get_contents(Config::Get('site/root_path').'/'.$path.'/templates/'.$filename);
        }
        return false;
    }   

    /**
     * @desc рыскрытие функций
     */
    private function DiscloseFunctions( $tpl ) {
        $tpl = preg_replace( '!\{(do|php):\s*([^\}]+)\}!i', '<?php \\2;?>', $tpl );
        $tpl = preg_replace( '!\{root:\s*([^\}]+)\}!i', '<?php echo Host::getWebPath("\\1");?>', $tpl );
        $tpl = preg_replace( '!\{static:\s*([^\}]+)\}!i', '<?php echo Host::getImgUrl("\\1");?>', $tpl );
        $tpl = preg_replace( '!\{estate_static:\s*([^\}]+)\}!i', '<?php echo Host::getImgUrl("\\1", null, "estate");?>', $tpl );
        $tpl = preg_replace( '!\{numeric:\s*([^\}]+)\}!i', "<?php echo number_format(\\1,0,'.',' ');?>", $tpl );
        $tpl = preg_replace( '!\{(quoted|escape):\s*([^\}]+)\}!i', '<?php echo isset(\\2)?htmlentities(\\2,ENT_COMPAT,"UTF-8"):""?>', $tpl );
        $tpl = preg_replace( '!\{(strip|htmlquoted|htmlescape):\s*([^\}]+)\}!i', '<?php echo isset(\\2)?Convert::CleanHtml(\\2):""?>', $tpl );
        $tpl = preg_replace( '!\{(stripall):\s*([^\}]+)\}!i', '<?php echo isset(\\2)?strip_tags(\\2):""?>', $tpl );
        $tpl = preg_replace( '!\{(phone):\s*([^\}]+)\}!i', '<?php echo isset(\\2)?Convert::ToPhone(\\2, 812, 8, 1):""?>', $tpl );
        $tpl = preg_replace( '!\{(numberformat):\s*([^\}]+)\}!i', '<?php echo isset(\\2)?Convert::ToNumber(\\2):""?>', $tpl );
        $tpl = preg_replace( '!\{(numberformat_to_mln):\s*([^\}]+)\}!i', '<?php echo isset(\\2)?Convert::ToFloat(\\2,true,"mln"):""?>', $tpl );
        $tpl = preg_replace( '!\{(squareformat):\s*([^\}]+)\}!i', '<?php print isset(\\2)?Convert::ToSquare(\\2):""?>', $tpl );
        $tpl = preg_replace( '!\{(suffix):\s*([^\}]+),([^\}]+),([^\}]+),([^\}]+),([^\}]+)\}!i', '<?php echo Convert::ToNumber(\\2)." ".makeSuffix(\\2, \\3, array(\\4,\\5,\\6))?>', $tpl );
        $tpl = preg_replace( '!\{(suffix_word):\s*([^\}]+),([^\}]+),([^\}]+),([^\}]+),([^\}]+)\}!i', '<?php echo makeSuffix(\\2, \\3, array(\\4,\\5,\\6))?>', $tpl );
        return $tpl;
    } 

    /**
     * @desc рыскрытие переменных
     */
    private function DiscloseVars( $tpl, $set_values = false ) {
        // простая переменная или свойство класса
        $tpl = preg_replace( '|\{([\$\[\]\w\d\_\'\"(->)(::)]+)\}|i', '<?php echo isset(\\1)?\\1:""; ?>', $tpl );
        $bkp = $tpl;
        if($set_values) {
            try {
                eval("\$tpl = \"$tpl\";");
            } catch (Exception $e) {
                $tpl = $bkp;
            }
        }
        return $tpl;
    }

    /**
     * @desc раскрытие условного ветвления
     */
    private function DiscloseBranches( $tpl ) {
        // условные блоки
        $tpl = str_replace( '{else', '{}else', $tpl );
        $tpl = preg_replace( '|\{(\})?(else)?if\s+(!)?(\$[^\}]+)\}|i', '<?php \\1\\2if(\\3((\\4)==true)){?>', $tpl );
        $tpl = preg_replace( '|\{(\})?(else)?if(!)?eq\s+(\$[^\;\,]+)[\;\,]([^\}]+)\}|i', '<?php \\1\\2if(isset(\\4) && \\3(\\4 == \\5)){?>', $tpl );
        $tpl = preg_replace( '|\{(\})?(else)?if(!)?lt\s+(\$[^\;\,]+)[\;\,]([^\}]+)\}|i', '<?php \\1\\2if(isset(\\4) && \\3(\\4 < \\5)){?>', $tpl );
        $tpl = preg_replace( '|\{(\})?(else)?if(!)?lte\s+(\$[^\;\,]+)[\;\,]([^\}]+)\}|i', '<?php \\1\\2if(isset(\\4) && \\3(\\4 <= \\5)){?>', $tpl );
        $tpl = preg_replace( '|\{(\})?(else)?if(!)?gt\s+(\$[^\;\,]+)[\;\,]([^\}]+)\}|i', '<?php \\1\\2if(isset(\\4) && \\3(\\4 > \\5)){?>', $tpl );
        $tpl = preg_replace( '|\{(\})?(else)?if(!)?gte\s+(\$[^\;\,]+)[\;\,]([^\}]+)\}|i', '<?php \\1\\2if(isset(\\4) && \\3(\\4 >= \\5)){?>', $tpl );
        $tpl = preg_replace( '|\{(\})?(else)?if(!)?like\s+(\$[^\;\,]+)[\;\,]([^\}]+)\}|i', '<?php \\1\\2if(isset(\\4) && \\3(strpos(\\4, \\5)===0)){?>', $tpl );
        $tpl = preg_replace( '|\{(\})?(else)?if(!)?in\s+(\$[^\;\,]+)[\;\,]([^\}]+)\}|i', '<?php \\1\\2if(isset(\\4) && \\3in_array(\\4, array(\\5))){?>', $tpl );
        $tpl = preg_replace( '|\{(\})?(else)?if(!)?empty\s+(\$[^\}]+)\}|i', '<?php \\1\\2if(\\3empty(\\4)){?>', $tpl );
        $tpl = str_replace( '{}else}', '<?php }else{ ?>', $tpl );
        // закрытие условных блоков
        $tpl = preg_replace( '|\{\/if\}|i', '<?php }?>', $tpl );


        return $tpl;
    }

    /**
     * @desc раскрытие циклов
     */
    private function DiscloseLoops( $tpl ) {
        $res = preg_match_all( '|\{loop\s+([\$\w\d\_]+)\s*[\;\,]\s*([\$\w\d\_]+)\s*[\;\,]\s*([\$\w\d\_]+)\}|i', $tpl, $matches, PREG_SET_ORDER );
        foreach( $matches as $match ) {
            $start = strpos( $tpl, $match[0] );
            if( $start!==false ) {
                $end = strpos( $tpl, '{/loop ' . $match[1] . '}', $start );
                $block = substr( $tpl, $start + strlen( $match[0] ), $end - $start - strlen( $match[0] ) );
                $body = $this->CreatePHP( $block );
                $tpl = substr( $tpl, 0, $start ) . '<?php foreach(' . $match[1] . ' as ' . $match[2] . '=>' . $match[3] . '){ ?>' . $body . '<?php }?>' . substr( $tpl, $end + strlen( '{/loop ' . $match[1] . '}' ) );
            }
        }
        return $tpl;
    }

    /**
     * @desc translate block of text from template in PHP-notation
     */
    private function CreatePHP( $tmpl ) {
        // сохраняем и временно убираем свободные от преобразований зоны
        $count = preg_match_all('|\{literal\}(.*)\{\/literal\}|Umsi', $tmpl, $_matches, PREG_SET_ORDER);
        if($count) $tmpl = preg_replace('|\{literal\}(.*)\{\/literal\}|Umsi','%literal%%/literal%', $tmpl);
        // Раскрываем встроенные шаблонные функции управления выводом
        $tmpl = $this->DiscloseFunctions($tmpl);
        // Преобразуем циклы в шаблоне
        $tmpl = $this->DiscloseLoops($tmpl);
        // Преобразуем ветвление в шаблоне
        $tmpl = $this->DiscloseBranches($tmpl);
        // Раскрываем переменные и константы
        $tmpl = $this->DiscloseVars($tmpl);
        // восстанавливаем свободные от преобразований зоны
        foreach($_matches as $_match){
            $pos = strpos($tmpl, '%literal%%/literal%');
            $tmpl = substr($tmpl, 0, $pos).$_match[1].substr($tmpl, $pos+19);
        }
        return $tmpl;
    }

    /**
     * @desc combine all templates in one
     */
    public function Combine() {
        do {
            $combined = 0;
            $pos = strpos( $this->template, "{include " );
            if( $pos !== false ) {
                $endpos = strpos( $this->template, "}", $pos + 9 );
                $filename = substr( $this->template, $pos + 9, $endpos - $pos - 9 );
                $combined++;
                $inc_tpl = new Template($filename, '', $this->recursive_counter+1);
                //$inc_content = $inc_tpl->Processing(); - в шаблон вставляем чистый php код, а не сгенеренные данные из include, иначе генерится огромное кол-во разных template на один модуль
                $this->template = str_replace("{include $filename}", $inc_tpl->template, $this->template);
                unset($inc_content);
                unset($inc_tpl);
            }
        }
        while($combined>0);
        return true;
    }
}

/**
* FileCache - static class for dynamic templates caching
*/
class FileCache{
    private static $cache_path = 'filecache';
    
    public static function Init($cache_path=null){
        if(!empty($cache_path)) self::$cache_path = $cache_path;
    }
    
    /**
    * Write data to cache
    * @param string signature of object (for hash calculate)
    * @param mixed data to write
    * @param int cache type
    * @return bool
    */
    public static function Write(&$signature, &$data){
        global $temp;
        $res = false;
        $buffer = null;
        if(is_array($data) || is_object($data)) {
            $buffer = $data;
            $data = addslashes(serialize($data));
        }
        $hash = self::GetHach($signature);
        $temp['data'][] = '; data:'.$data;
        $temp['hash'][] = '; hash:'.$hash;
        $filename = self::GetCacheFilePath($hash);
        if(self::makeDir($filename)) {
            $temp['makedir'][] = '; dir:'.$filename;
            $res = file_put_contents($filename, $data);
            if($res!==false) {
                chmod( $filename, 0666 );
            }
        }
        if($buffer!==null) {
            $data = $buffer;
            unset($buffer);
        }
        return $res!==false ? $filename : $res;
    }

    /**
    * Read data from cache
    * @param string signature of object (for hash calculate)
    * @param int cache type
    * @param int time of cache actuality (sec)
    * @return mixed data or null
    */
    public static function Read(&$signature){
        $hash = self::GetHach($signature);
        $file = self::GetCacheFilePath($hash);
        if(!is_file($file)) return null;
        $data = file_get_contents($file);
        return $data;
    }

    /**
     * Clear all cached files and dirs
     * @param mixed type of cache
     */
    public static function Clear() {
        self::rClear(self::$cache_path);
    }

    private static function rClear($path) {
        if( is_dir( $path ) ) {
            if( $dir = opendir( $path ) ) {
                while( ($element = readdir( $dir )) !== false ) {
                    if($element != "." && $element != ".." && is_dir( $path . "/" . $element )){
                        self::rClear($path.'/'.$element);
                        rmdir( $path . "/" . $element );
                    } elseif($element != "." && $element != ".." && is_file( $path . "/" . $element )) unlink( $path . "/" . $element );
                }
                closedir( $dir );
            }
        }
    }

    /**
    * Get hash for data
    * @param mixed $data
    * @return string
    */
    public static function GetHach(&$data){
        return sha1(addslashes(serialize($data)));
    }
    
    /**
    * Get path to cached file
    * @param string hash string
    * @param integer cache type
    * @return string path to file
    */
    private static function GetCacheFilePath( $hash ) {
        $path = self::$cache_path . "/" . substr($hash,0,1) . "/" . $hash . ".cache";
        return $path;
    }
    
    /**
    * Check for folder is exists and create it recursively if it need
    * @param string $path path to the file
    * @return boolean
    */
    private static function makeDir($path){
        if(empty($path)) return false;
        $dir = dirname($path);
        if(is_dir($dir)) return true;
        $result = true;
        if(!mkdir($dir, 0777, true)) return false;
        else chmod($dir, 0777);
        return true;
    }
    
    /**
     * Checks Cached HTML or Cached Query path
     * @param string $path path to template
     * @param integer $cacheType cache type
     * @param integer $cacheTime time while cache file will Alive
     * @return mixed path to cached html file or false if file not found or expired
     */
    public static function GetCachedPath( &$cacheString ) {
        $path = self::GetCacheFilePath( self::GetHach($cacheString) );
        if(!is_file($path)) return false;
        return $path;
    }
}
?>
