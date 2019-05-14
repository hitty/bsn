<?php
/**    
* Класс REST API Sendpulse
*/
require("includes/sendpulse/ApiInterface.php");
require("includes/sendpulse/ApiClient.php");
require("includes/sendpulse/Storage/TokenStorageInterface.php");
require("includes/sendpulse/Storage/FileStorage.php");
require("includes/sendpulse/Storage/SessionStorage.php");
require("includes/sendpulse/Storage/MemcachedStorage.php");
require("includes/sendpulse/Storage/MemcacheStorage.php");

use Sendpulse\RestApi\ApiClient;
use Sendpulse\RestApi\Storage\FileStorage;

class Sendpulse extends ApiClient {
    public $user_id = '8021528a90d4c65abdea68d68200f041';          
    public $secret = '525200ad13a67a8d016c8dc930ddd5c7';         
    public $path_to_attached_files = '';         
    public $books =  array( 
        'my' => 1866411,
        'test' => 1852192,
        'subscriberes' => 1852595
    );
          
    public $book_id = 1852192 ;        
    public $bsn_subscriberes_book_id = 1852595 ;        
    public $sender_email = 'no-reply@bsn.ru' ;        
    public $sender_name = 'BSN.ru' ;        
    
    public $website_id = 3676 ;        
    
    public function __construct( $book_type = false){
        parent::__construct( $this->user_id, $this->secret, new FileStorage() );
        if( !empty( $book_type ) && !empty( $this->books[ $book_type ] ) ) $this->book_id = $this->books[ $book_type ]; 
    }

    /* список адресных книг */
    public function listAddressBooks( $limit = null, $offset = null ){
        return parent::listAddressBooks( $limit, $offset );
    }

    /**
    * Добавление email в адресную книгу
    *  
    * @param mixed $book_id
    * @param mixed $emails
    * @return stdClass
    */
    
    public function addEmails( $book_id = false, $emails ){
        $book_id = empty( $book_id ) ? $this->book_id : $book_id;
        return parent::addEmails( $book_id, $emails );
    }
    
    /**
     * Создание новой кампании
     *
     * @param        $sender_name
     * @param        $sender_email
     * @param        $subject
     * @param        $body
     * @param        $book_id
     * @param string $name
     * @param string $attachments
     * @param string $type
     * @param date   $send_date Y-m-d H:i:s (например: 2016-02-02 23:34:23) 
     *
     * @return mixed
     */
    public function createCampaign(
        $subject,
        $body,
        $name = '',
        $send_date = '',
        $sender_name = '',
        $sender_email = '',
        $book_id = false,
        $attachments = '',
        $type = ''
    ) {
        $book_id = empty( $book_id ) ? $this->book_id : $book_id; 
        $sender_name = empty( $sender_name ) ? $this->sender_name : $sender_name; 
        $sender_email = empty( $sender_email ) ? $this->sender_email : $sender_email; 
        return parent::createCampaign( $sender_name, $sender_email, $subject, $body, $book_id, $name, $attachments, $type, $send_date );
    }
    
    /**
     * SMTP: send mail
     *
     * @param $email
     *
     * @return stdClass
     */
    public function sendMail(
        $subject,
        $body,
        $reciever_name = '',
        $reciever_email = '',
        $sender_name = '',
        $sender_email = '',
        $emails = '',
        $attachments = ''
    )
    {
        if ( empty( $subject ) || empty( $body ) || ( empty( $reciever_email ) && empty( $emails ) ) ) return $this->handleError( 'Empty email data' );
            
        $reciever_email = DEBUG_MODE ? Config::Get( 'emails/web2') : $reciever_email; 
        $sender_name = empty( $sender_name ) ? $this->sender_name : $sender_name; 
        $sender_email = empty( $sender_email ) ? $this->sender_email : $sender_email; 

        $data = array(
            'html' => $body,
            'text' => $subject,
            'subject' => $subject,
            'from' => array(
                'name' => $sender_name,
                'email' => $sender_email,
            ),
            'to' => 
            empty( $emails ) ? 
                array(
                    array(
                        'name' => !empty( $reciever_name ) ? $reciever_name : '',
                        'email' => $reciever_email,
                    ),
                ) : $emails,
            'attachments' => empty( $attachments ) ? false : array ( $attachments['path'] => $attachments['title'] )
        );
        
        return parent::smtpSendMail( $data );
    }
    
    /**
     * Create new push campaign
     *
     * @param       $taskInfo
     * @param array $additionalParams
     *
     * @return stdClass
     */
    public function createPush($title, $body, $link, $image = '' )
    {
        $data = $additionalParams = array();

        $data['ttl'] = 0;
        $data['title'] = $title;
        $data['website_id'] = $this->website_id;
        $data['body'] = $body;
        
        /*
        
        if( !empty( $image ) ) {
            $additionalParams['image'] = json_encode(
                array(
                    'name' => $image,
                    'data' => base64_encode( file_get_contents( $image ) )
                )
            );
        }
        */
        
        $additionalParams['link'] = $link;

        return parent::createPushTask( $data, $additionalParams );
    }
}

?>