<?
class Log
{
   
    private static $path = 'logs';
    private static $filename = '';
    
    public static function Write($type, $data, $clear = false){
        self::setFilename( $type );
        if( empty( $clear ) ){
            $filedata = self::loadData( self::$filename );
            if( !is_array( $filedata ) ) $filedata = [];
            $array = array_merge( $filedata, array( ( count($filedata) - 1 ) => $data ) );
        }
        file_put_contents( self::$filename, serialize( $array ) );
    }
    
    public static function loadData( $type ){ 
        if( empty( self::$filename ) ) self::setFilename( $type );
        if( file_exists( self::$filename ) ) return unserialize( file_get_contents( self::$filename ) );
        else return false;
    }    
    
    public static function clearData( $type ){ 
        file_put_contents( self::$filename, '' );
    }    
    
    private static function setFilename( $type ){
        self::$filename = ROOT_PATH . "/" . self::$path . '/' . $type . '.log';
    }
}
?>