<?php
class vk {
    private $token;
    private $app_id;
    //ID группы или страницы пользователя
    private $group_id;
    public function __construct( $token, $app_id, $group_id ) {
        $this->token = $token;
        $this->app_id = $app_id;
        $this->group_id = $group_id;
    }
    //постинг на стену
    public function post( $desc, $photo = false,$link = false) {
        $post_data = array(
                    'owner_id' => -$this->group_id,
                    'from_group' => 1,
                    'message' => $desc
        );
        
        if(!empty($photo)) $post_data['attachments'] = 'photo-' . $this->group_id . '_' . $photo;
        if(!empty($link)) $post_data['attachments'] .= ','.$link;
        $data = json_decode(
                    $this->execute(
                        'wall.post',
                        $post_data
                    )
                );
        if( isset( $data->error ) ) {
            return $this->error( $data );
        }
        return $data->response->post_id;
    }
    //создание альбома
    public function create_album( $name, $desc ) {
        $data = json_decode(
                    $this->execute(
                        'photos.createAlbum',
                        array(
                            'title' => $name,
                            'gid' => $this->group_id,
                            'description' => $desc,
                            'comment_privacy' => 1,
                            'privacy' => 1
                        )
                    )
                );
        if( isset( $data->error ) ) {
            return $this->error( $data );
        }
        return $data->response->aid;
    }
    //получение кол-ва фотографий в альбоме
    public function get_album_size( $id ) {
        $data = json_decode(
                    $this->execute(
                        'photos.getAlbums',
                        array(
                            'oid' => -$this->group_id,
                            'aids' => $id
                        )
                    )
                );
        if( isset( $data->error ) ) {
            return $this->error( $data );
        }
        return $data->response['0']->size;
    }
    //загрузка фотографии
    public function upload_photo( $file, $album_id, $desc ) {
        $data = json_decode(
                    $this->execute(
                        'photos.getUploadServer',
                        array(
                            'aid' => $album_id,
                            'gid' => $this->group_id,
                            'save_big' => 1
                        )
                    )
                );
        if( isset( $data->error ) ) {
            return $this->error( $data );
        }
        $ch = curl_init( $data->response->upload_url );
        curl_setopt ( $ch, CURLOPT_HEADER, false );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt ( $ch, CURLOPT_POST, true );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, array( 'file1' => '@' . $file ) );
        $data = curl_exec($ch);
        curl_close($ch);
        $data = json_decode( $data );
        if( isset( $data->error ) ) {
            return $this->error( $data );
        }
        $data = json_decode(
                    $this->execute(
                        'photos.save',
                        array(
                            'aid' => $album_id,
                            'gid' => $this->group_id,
                            'server' => $data->server,
                            'photos_list' => $data->photos_list,
                            'hash' => $data->hash,
                            'caption' => $desc
                        )
                    )
                );
        if( isset( $data->error ) ) {
            return $this->error( $data );
        }
        return $data->response['0']->pid;
    }
    private function execute( $method, $params ) {
        $ch = curl_init( 'https://api.vk.com/method/' . $method . '?access_token=' . $this->token );
        curl_setopt ( $ch, CURLOPT_HEADER, false );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt ( $ch, CURLOPT_POST, true );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $params );
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
    private function error( $data ) {
        //обработка ошибок
        return false;
    }
}

  
?>
