<?php
require_once('includes/getid3/getid3.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');
class Videos {
    
    public static $__folder_options=array(
							'sm'=>array(90,90,'cut',65),
							'med'=>array(560,415,'cut',75),
							'big'=>array(800,600,'',70)
							); 				// свойства папок для загрузки и формата видео
    /**
    * получение главной фотки
    * @param string $table - основная таблица
    * @param integer $id - ID объекта в основной таблице
    * @return array of arrays
    */
    public static function getMainVideo($table, $id, $suffix=null){
        global $db;
        
		$sql = "SELECT 
					`videos`.`id`,
					`videos`.`name`,
					LEFT (`videos`.`name`,2) as `subfolder`
				FROM ".Config::$sys_tables[$table.'_videos']." videos
				LEFT JOIN ".Config::$sys_tables[$table].$suffix." `main` 
					ON `main`.`id_main_video` = `videos`.`id`
				WHERE `main`.`id`=".$id."
				LIMIT 1 ";
        $rows = $db->fetch($sql);
		return !empty($rows)>0 ? $rows : false;
    }
   
    /**
    * установка флага "главное видео" для объекта
    * @param string $table - основная таблица
    * @param integer $id - ID объекта в основной таблице
    * @param integer $id_video - ID видео
    * @return boolean
    */
    public static function setMain($table, $id, $id_video=null, $suffix=null, $external_video_src = false){
        global $db;
		if($id>0 && $id_video>0){
		} else if($id>0 && $id_video==null){
			if(!empty($external_video_src)){
               $video = $db->fetch("SELECT `id` FROM ".Config::$sys_tables[$table.'_videos']." WHERE `id_parent".$suffix."` = ".$id." AND external_video_src = ? LIMIT 1", $external_video_src); 
               if(!empty($video)) $id_video = $video['id'];
            } else {
                $nextVideo = $db->fetch("SELECT `id` FROM ".Config::$sys_tables[$table.'_videos']." WHERE `id_parent".$suffix."` = ".$id." ORDER BY id LIMIT 1");
			    if($nextVideo['id']>0) $id_video = $nextVideo['id'];
            }
		}
		else return false;
        if(!empty($id_video)) $res = $db->query("UPDATE  ".Config::$sys_tables[$table].$suffix." SET `id_main_video` = ".$id_video.( in_array( $table, array('live', 'build', 'commercial', 'country' ) ) ? ", has_video = 2" : "" )." WHERE `id` = ".$id);
		return true;
		
    } 

     /**
    * получение списка видео
    * @param string $table - основная таблица
    * @param integer $id - ID объекта в основной таблице
    * @return array of arrays
    */
    public static function getList($table, $id = false, $suffix=null, $where = ''){
         global $db;
         $where = !empty($where) ? $where : "`videos`.`id_parent".$suffix."` = ".$id;
		 $sql = "SELECT 
                `videos`.*,
                ".Config::$sys_tables[$table.'_videos_photos'].".name as photo_name,
                LEFT(".Config::$sys_tables[$table.'_videos_photos'].".name,2) as `photo_subfolder`
		   FROM ".Config::$sys_tables[$table.'_videos']." videos 
           LEFT JOIN ".Config::$sys_tables[$table].$suffix." main ON `main`.`id` = `videos`.`id_parent".$suffix."`
		   LEFT JOIN ".Config::$sys_tables[$table.'_videos_photos']." ON `videos`.`id_main_photo` = ".Config::$sys_tables[$table.'_videos_photos'].".`id`
		   WHERE ".$where."
		   GROUP BY `videos`.id
           ORDER BY `videos`.`id` ";
        $rows = $db->fetchall($sql);   
        if(empty($rows)) return [];
        return $rows;
    }
    
    /**
    * загрузка видео
    * 
    * @param mixed $table - основная таблица
    * @param mixed $id - ID объекта в основной таблице
    * @param mixed $suffix
    * @param mixed $external_video_src - URL видео
    * @param mixed $internal_video_src
    * @param mixed $min_width
    * @param mixed $min_height
    * @param mixed $high_quality
    * @param mixed $watermark_src
    * @param mixed $watermark_alpha_level
    * @param mixed $max_width
    * @param mixed $max_height
    * @param mixed $fixed_sizes
    */
    public static function Add($table, $id, $suffix=null, $mode = 'only_add'){
        global $db, $errors_log;
        
		// временная папка для загрузки видео
        $video_folder = Config::$values['video_folder'];
		$tempFolder = ROOT_PATH.'/'.$video_folder.'/'; 
        
		//если передана ссылка на скачивание
        if(!empty($external_video_src) && empty($internal_video_src)) {
            $size =  getimagesize($external_video_src);
            $video_url = self::Download($external_video_src,$tempFolder);
        }
        elseif(!empty($internal_video_src)) $video_url = $internal_video_src;
        if(empty($video_url) && !empty($external_video_src)) return false;
        else{
            if(!empty($_FILES)){
                $array_key = array_keys($_FILES);
                $array_key = $array_key[0];
            }
            
            // проверка типа файла
		    $fileTypes = array('mp4', '3gp', 'ogg', 'avi', 'mov', 'wmf', 'wmv', 'mpeg'); // File extensions
		    $fileParts = !empty($video_url) ? pathinfo($video_url) : (!empty($_FILES[$array_key]['name']) ? pathinfo($_FILES[$array_key]['name']) : false);
            if(!empty($fileParts['extension'])){
                $targetExt = $fileParts['extension'];
		        
		        if(empty($video_url)) $tempFile = $_FILES[$array_key]['tmp_name'];
		        $targetFile = md5(microtime()).'.' . $targetExt;
		        $subFolder = substr($targetFile,0,2);              
                self::makeDir($tempFolder . $subFolder . '/' . $targetFile); 
		        // загрузка видео в папки
                if (in_array(strtolower($fileParts['extension']),$fileTypes)) {
                    move_uploaded_file($tempFile, $tempFolder . $subFolder . '/' . $targetFile);
                    $getID3 = new getID3; 
                    $file = $getID3->analyze($tempFolder . $subFolder . '/' . $targetFile);
                    $addition_sql = "";
                    if(!empty($external_video_src)) $addition_sql = ", `external_video_src`='".$external_video_src."'";
                    //запись имени видео в БД
                    if(
                        $db->query("INSERT INTO ".Config::$sys_tables[$table.'_videos']." SET 
                                            `original_name` = '".$targetFile."', 
                                            `name` = '".$_FILES[$array_key]['name']."', 
                                            `id_parent".$suffix."` = ".$id.$addition_sql
                        )
                    ){
                        $id_video = $db->insert_id;
					    //запись главной фотки если она первая
					    $getMainVideo = $db->query("SELECT ".Config::$sys_tables[$table].$suffix.".id_main_video
                                                    FROM ".Config::$sys_tables[$table].$suffix."
                                                    RIGHT JOIN ".Config::$sys_tables[$table.'_videos']." ON ".Config::$sys_tables[$table.'_videos'].".id = ".Config::$sys_tables[$table].$suffix.".id_main_video 
                                                    WHERE ".Config::$sys_tables[$table].$suffix.".id_main_video > 0 AND ".Config::$sys_tables[$table].$suffix.".id = ".$id);
                        if($getMainVideo->num_rows == 0) self::setMain($table, $id, $id_video, $suffix);
                        
				    } else return false; 
		        } else return false;
            }  else return false;
        } 
        
    }
    public static function Convert($table, $id, $item){
        global $db;
        $db->query("UPDATE ".Config::$sys_tables[$table.'_videos']." SET status = 2 WHERE id = ?", $id);
        $targetFile = $item['original_name'];
        $subFolder = substr($targetFile,0,2);
        $tempFolder = ROOT_PATH.'/'.Config::$values['video_folder'].'/';
        $fileinfo = Filespot::upload($targetFile);
        if(empty($fileinfo['object'])) $db->query("UPDATE ".Config::$sys_tables[$table.'_videos']." SET status = 1 WHERE id = ?", $id);
        $getID3 = new getID3; 
        $file = $getID3->analyze($tempFolder . $subFolder . '/' . $targetFile);
        $addition_sql = "";
        //запись имени видео в БД
        $db->query("UPDATE ".Config::$sys_tables[$table.'_videos']." SET `external_id` = ?, name = ?, filesize = ?, filelength = ?, status = 3 WHERE id = ?", $fileinfo['object']['id'], $fileinfo['object']['cdn_url'], $fileinfo['object']['size'], $fileinfo['object']['advanced']['format']['duration'], $id);
        //удаление файла
        if(file_exists($tempFolder . $subFolder . '/' . $targetFile)) unlink($tempFolder . $subFolder . '/' . $targetFile);
        //загрузка превьюшек                    
        if(!empty($fileinfo['object']['previews'][0])) Photos::Add($table.'_videos', $id, '', 'http://'.$fileinfo['object']['previews'][0]);
        if(!empty($fileinfo['object']['previews'][1])) Photos::Add($table.'_videos', $id, '', 'http://'.$fileinfo['object']['previews'][1]);
        if(!empty($fileinfo['object']['previews'][2])) Photos::Add($table.'_videos', $id, '', 'http://'.$fileinfo['object']['previews'][2]);
        return array(
                'file_name'=> !empty($fileinfo['object']['previews'][0]) ? $fileinfo['object']['previews'][0] : '',
                'fileinfo' => $fileinfo['object'],
                'video_id' => $id
                );        
    }
     /**
    * название видео
    * @param string $table - основная таблица
    * @param integer $id - ID объекта в основной таблице
    * @param integer $title - название
    * @return array of arrays
    */
    public static function setTitle($table, $id, $title){
         global $db;
         return $db->query("UPDATE ".Config::$sys_tables[$table.'_videos']." SET title = ? WHERE id = ?", $title, $id); 
    }       
     
    /**
    * удаление видео из базы и из папки на сервере
    * @param string $table - основная таблица
    * @param integer $id_video - ID видео в таблице с фотками
    */
    public static function Delete($table, $id_video, $suffix=null){
        global $db, $video_folder;
        //определяем имя файла (для удаления всех видео с таким именем)
        $video_name = $db->fetch("SELECT LEFT(`name`,2) as `subfolder`, `name`, `external_id` FROM ".Config::$sys_tables[$table.'_videos']." WHERE `id_parent` = ?",$id_video); 
        if(empty($video_name)) return false;        
        $sql = "SELECT `video`.`name`, `video`.`id` as video_id, `estate_table`.`id`
                FROM ".Config::$sys_tables[$table.'_videos']." `video`
                LEFT JOIN ".Config::$sys_tables[$table].$suffix." `estate_table` ON `estate_table`.`id` = `video`.`id_parent".$suffix."`
                WHERE `video`.`id_parent` = '".$id_video."'";
        $rows = $db->fetchall($sql);
        if(empty($rows)) return false;
        //удаление видео с сервера
        foreach($rows as $k=>$item){
            Filespot::DeleteFromServer($video_name['external_id']);
            //если удаленная видео является главной, то переназначаем главную
            $main_video = self::getMainVideo($table, $item['id'], $suffix);
            $del = $db->query("DELETE FROM ".Config::$sys_tables[$table.'_videos']." WHERE `id` = ?", $item['video_id']);
            if($main_video['id']>0 && $main_video['id']==$id_video) self::setMain($table, $item['id'], 0, $suffix);
        }    
        $db->query("UPDATE  ".Config::$sys_tables[$table].$suffix." SET `id_main_video` = 0, has_video = 1 WHERE `id` = ?", $id_video);
        return !empty($del);
    }
    /**
    * удаление всех видео из базы и из папки на сервере
    * @param string $table - основная таблица
    * @param integer $id_parent - ID предка
    */
    public static function DeleteAll($table, $id_parent, $suffix=null){
        global $db, $video_folder;
		$sql = "SELECT *, LEFT(`name`,2) as `subfolder` FROM ".Config::$sys_tables[$table.'_videos']." WHERE `id_parent".$suffix."` IN (".$id_parent.")";
		$rows = $db->fetchall($sql);
		if(empty($rows)) return false;
		$cnt=0;
		$unlink_flag = true;
		foreach($rows as $key=>$value){
            if (file_exists(ROOT_PATH.'/'.$video_folder."/".$value['subfolder']."/".$value['name']))
				if(!unlink(ROOT_PATH.'/'.$video_folder."/".$value['subfolder']."/".$value['name'])) { $unlink_flag = false; break; }
			$del = $db->query("DELETE FROM ".Config::$sys_tables[$table.'_videos']." WHERE `id_parent".$suffix."` = ?",$id_parent);
			
		}
		$db->query("UPDATE ".Config::$sys_tables[$table].$suffix." SET `id_main_video` = 0 ".( in_array( $table, array('live', 'build', 'commercial', 'country' ) ) ? ", has_video = 1" : "" )." WHERE id = ?",$id_parent);
		return !empty($del) && !empty($unlink_flag);
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
    * Метод ресайза видео
    * 
    * @return boolean
    */

    public static function videoResize($src) {
        
        return true;        
    } 
}

class Filespot extends Videos{
    private static $userKey = 'n7i12TbY1knmT5xTeddbM638cxgbY28cH9pMPLyjgZ8=';
    private static $userId = '44XQE5XAhZAWibY7ypsqIN68I7XaHeames2Lj7ztHZY=';
    private static $url = 'https://api.platformcraft.ru/1/';
    private static $time = [];

    /**
    * Получение хеша для стриминга
    */
    public static function makeHash($uri, $method = 'GET') {
        self::$time = time();
        $message = $method . '+api.platformcraft.ru/1/' . $uri . '?apiuserid='.self::$userId.'&timestamp='.self::$time;
        return hash_hmac('sha256', $message, self::$userKey);                      
    }     

    /**
    * загрузка на партнерский сервер
    * @param string $targetFile - имя файла
    * @return boolean
    */
    public static function upload($targetFile){
        global $db;
        $targetFile = 'https://st1.bsn.ru/img/videos/'.substr($targetFile,0,2).'/'.$targetFile;
        $url = 'https://api.platformcraft.ru/1/download';
        $hash = self::makeHash('download', 'POST');
        $params = array(
            'apiuserid' =>  self::$userId,
            'timestamp' =>  self::$time,
            'hash' =>  $hash,
            'url' => $targetFile,
            'path' => '/objects'
        );
        $result = self::postHttp($url, $params);
        if(empty($result['content'])) return false;
        $content = json_decode($result['content'], true);
        $fileinfo = pathinfo($targetFile);
        //получение статуса закачки файла
        $task_id = $content['task_id'];         
        do{
            $uploadResult = self::uploadTask($task_id);
            $status = $uploadResult['task']['status'];
            usleep(2000000); //0.2sec
        } while ($status == 'Progress');

        if(empty($uploadResult)) return false;
        
        //перекодирование файла
        if($fileinfo['extension'] != 'mp4'){
            $transcode = self::transcode($uploadResult['files'][0]);
            if(empty($transcode)) return false;
        
        
            //получение статуса перекодирования файла
            $task_id = $transcode->task_id;         
            do{
                $uploadResult = self::transcodeTask($task_id);
                $status = $uploadResult['task']['status'];
                usleep(2000000); //0.2sec
            } while ($status == 'Progress');

            if(empty($uploadResult)) return false;
        }
        //получение информации о файле
        $objectInfo = self::objectInfo($uploadResult['files'][0]);
        
        return $objectInfo;
    }    
    /**
    * получение информации и закачке файла
    * @param string $task_id - id процесса
    * @return boolean
    */    
    public static function uploadTask($task_id){
        $hash = self::makeHash('download_tasks/'.$task_id);
        $url = 'https://api.platformcraft.ru/1/download_tasks/'.$task_id.'?apiuserid='.self::$userId.'&timestamp='.self::$time.'&hash='.$hash;
        return json_decode(file_get_contents($url), true);
    }    
    /**
    * получение информации и закачке файла
    * @param string $task_id - id процесса
    * @return boolean
    */    
    public static function objectInfo($id){
        $hash = self::makeHash('objects/'.$id);
        $url = 'https://api.platformcraft.ru/1/objects/'.$id.'?apiuserid='.self::$userId.'&timestamp='.self::$time.'&hash='.$hash;
        return json_decode(file_get_contents($url), true);
    }
    /**
    * удаление файла с сервера
    * @param string $task_id - id процесса
    * @return boolean
    */
    public static function DeleteFromServer($id){
        $hash = self::makeHash('objects/'.$id, 'DELETE');
        $url = 'https://api.platformcraft.ru/1/objects/'.$id.'?apiuserid='.self::$userId.'&timestamp='.self::$time.'&hash='.$hash;
        $delete = json_decode(self::sendData($url, false, 'DELETE'));
        return $delete;
    }
    /**
    * перекодирование файла 
    * @param string $id - id объекта
    * @return boolean
    */    
    public static function transcode($id){
        $hash = self::makeHash('transcoder/'.$id, 'POST');
        $url = 'https://api.platformcraft.ru/1/transcoder/'.$id.'?apiuserid='.self::$userId.'&timestamp='.self::$time.'&hash='.$hash;
        $params = array(
            'presets' => ['571e1a30702b930774ff22ea'],
            'path' => '/objects'
        );
        $str_data = json_encode($params);
        
        return json_decode(self::sendData($url, $str_data));
    }    
    /**
    * получение информации и перекодировании файла
    * @param string $task_id - id процесса
    * @return boolean
    */    
    public static function transcodeTask($task_id){
        $hash = self::makeHash('transcoder_tasks/'.$task_id);
        $url = 'https://api.platformcraft.ru/1/transcoder_tasks/'.$task_id.'?apiuserid='.self::$userId.'&timestamp='.self::$time.'&hash='.$hash;
        return json_decode(file_get_contents($url), true);
    }    
    
 /**
    make an http POST request and return the response content and headers
    @param string $url    url of the requested script
    @param array $data    hash array of request variables
    @return returns a hash array with response content and headers in the following form:
        array ('content'=>'<html></html>'
            , 'headers'=>array ('HTTP/1.1 200 OK', 'Connection: close', ...)
            )
    */  
    public static function postHttp($url, $data, $method = 'POST')
    {
        $data_url = http_build_query ($data);
        $data_len = strlen ($data_url);
        //echo '---'.$url.'---';
        return array ('content'=>file_get_contents ($url, false, stream_context_create (array ('http'=>array ('method'=>$method
                , 'header'=>"Connection: close\r\nContent-Length: $data_len\r\n"
                , 'content'=>$data_url
                ))))
            , 'headers'=>$http_response_header
        );
    }  
/**
    make an http POST request and return the response content and headers
    @param string $url    url of the requested script
    @param array $data    hash array of request variables
    @return returns a hash array with response content and headers in the following form:
        array ('content'=>'<html></html>'
            , 'headers'=>array ('HTTP/1.1 200 OK', 'Connection: close', ...)
            )
    */  
    public static function sendData($url, $post, $method = 'POST'){
      $ch = curl_init($url);
      $headers= array('Accept: application/json','Content-Type: application/json'); 
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);  
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      if($method == 'POST') curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
      $result = curl_exec($ch);
      curl_close($ch);  // Seems like good practice
      return $result;
    } 
    
}
?>
