<?php
/**    
* Класс для заявок
*/
class ConsultQuestion {
    
    public $id;                             //id вопроса
    public $id_initiating_user;              //id пользователя, оставившего вопрос
    public $id_respondent_user;             //id пользователя, которому оставили вопрос
    public $id_respondent_agency;           //id агентства которому оставили вопрос
    public $id_category;                    //id категории
    public $status;                         //статус вопроса
    public $visible_to_all;                 //видимость для всех (1-да, 2-нет)
    
    protected $data_array = [];        // данные вопроса
    protected $respondents_array = [];      // данные хозяев вопроса
    
    private $tables = [];              //все таблицы из Config
    
    /**
    * конструктор класса - создаем экземпляр либо для той что уже есть, либо по параметрам из формы
    * 
    * @param mixed $id - если создаем по существующей, ее id
    * @param mixed $create_params - если создаем из формы, параметры оттуда
    * @param ConsultQuestion $parent_app - если создаем копию, ее оригинал
    * @return ConsultQuestion
    */
    function __construct($id = false ,$create_params = null,ConsultQuestion $parent_app = null,$no_notify = null){
        $this->tables = Config::$sys_tables;
        switch(true){
            case !empty($id): $this->Init($id);break;
            case !empty($create_params): 
                $this->Create($create_params);
                if(empty($no_notify)) $this->sendModeratorNotification();
                break;
            default: $this->id = 0; return false;
        }
    }
    
    /**
    * создаем вопрос при отправке формы (создаем экземпляр и пишем в базу)
    * 
    * @param mixed $parameters - параметры переданные из формы оставления вопроса
    */
    private function Create( $parameters ) {
        global $db;
        global $ajax_result;
        //читаем переданные параметры
        $id = ( !empty( $parameters['id'] ) ? $parameters['id'] : 0 );
        $agency_id = (!empty($parameters['agency_id'])?$parameters['agency_id']:0);
        
        $title = $parameters['name'];
        $q_title = $parameters['title'];
        $q_body = $parameters['text'];
        $q_cat = $parameters['category'];
        $q_respondent = (!empty($parameters['responder'])?$parameters['responder']:0);
        
        $user_name = trim($parameters['name']);
        $user_email = trim($parameters['email']);
        
        //согласие на регистрацию
        $user_registration = (empty($parameters['reg_agree'])?"":$parameters['reg_agree']);
        
        $q_lifetime = $db->fetch("SELECT id ,lifetime
                                FROM ".$this->tables['consults_categories']." 
                                WHERE id = ? ",$q_cat);
        $q_lifetime = (int)$q_lifetime['lifetime'];
        
        //время закрытости вопроса
        if($q_lifetime % 60 == 0) $q_lifetime = (floor($q_lifetime/60))." ч.";
        else $q_lifetime = (($q_lifetime/60 > 0)?(floor($q_lifetime/60)." ч."):("")).(($q_lifetime % 60 > 0)?(($q_lifetime % 60)." мин."):(""));
        
        global $auth;
        //если все хорошо, пихаем в базу
        if(!empty($user_email) && Validate::isEmail($user_email) && !empty($user_name) && !empty($q_cat) && !empty($q_body) ){
            
            $this->data_array['status'] = 1;
            $this->data_array['title'] = $q_title;
            $this->data_array['question'] = $q_body;
            $this->data_array['name'] = $user_name;
            $this->data_array['email'] = Validate::isEmail($user_email)?$user_email:"";
            $this->data_array['id_category'] = $q_cat;
            //id пользователя которому была адресован вопрос
            $this->data_array['id_respondent_user'] = $q_respondent;
            $this->id_respondent_user = $q_respondent;
            //id пользователя задавшего вопрос
            $this->data_array['id_initiating_user'] = (empty($auth->id)?0:$auth->id);
            //флаг что вопрос создан с регистрацией пользователя
            $user_creating = false;
            //если пользователь не авторизован, поставил галочку регистрироваться, проверяем есть ли он и регистрируем его/корректируем данные вопроса
            if( empty( $this->data_array['id_initiating_user'] ) && !empty( $this->data_array['email'] ) && !empty( $user_registration ) ) {
                //проверяем, есть ли такой пользователь, если нету, регистрируем
                $exists = $db->fetch( "SELECT id,email,name FROM " . $this->tables['users'] . " WHERE email = ?", $this->data_array['email'] );
                if($exists){
                    
                    $this->data_array['id_initiating_user'] = $exists['id'];
                    $this->data_array['name'] = $exists['name'];
                    
                }else{
                     $new_user_data = Common::createUser(array('name'=>$this->data_array['name'],'login'=>$this->data_array['email'],'email'=>$this->data_array['email'],'user_activity'=>1,'id_user_type'=>2));
                     if(!empty($new_user_data)){
                         $this->data_array['id_initiating_user'] = $new_user_data['id'];
                         $user_creating = true;
                         $env = array(
                             'url' => Host::GetWebPath(),
                             'host' => Host::$host,
                             'author' => $this->data_array['name']
                         );
                         Response::SetArray('env', $env);
                         //данные для входа
                         Response::SetArray('reg_data',$new_user_data);
                         // инициализация шаблонизатора
                         $eml_tpl = new Template('/modules/consults/templates/mail_notify_asker.html');
                         // формирование html-кода письма по шаблону
                         $html = $eml_tpl->Processing();
                         
                        // формирование html-кода письма по шаблону
                        $html = $eml_tpl->Processing();         
                        if( !class_exists('Sendpulse') ) require("includes/class.sendpulse.php");
                        //отправка письма
                        $sendpulse = new Sendpulse( 'subscriberes' );
                        $emails = [
                            [ 'name' => '',                         'email'=> 'web@bsn.ru' ],
                            [ 'name' => $this->data_array['name'],  'email'=> $new_user_data['email'] ]
                        ];

                        $correct_send = $sendpulse->sendMail( 'Вы создали вопрос в сервисе Консультант ' . Host::$host, $html, '', '', '', '', $emails );
                     }
                } 
            }
            
            $this->id_initiating_user = $this->data_array['id_initiating_user'];
            
            //читаем информацию по хозяевам вопроса (при наличии)
            if(!empty($this->id_respondent_user)){
                $respondent_info = [];
                $respondent_user_info = $db->fetch("SELECT ".$this->tables['users'].".id AS id_user,
                                                id_agency,email AS user_email,
                                                phone AS user_phone,
                                                CONCAT(name,' ',lastname) AS user_full_name,
                                                id_tarif AS user_tarif,
                                                ".$this->tables['tarifs'].".title AS user_tarif_title,
                                                login,
                                                id_agency,
                                                (id_tarif>0) AS user_is_specialist,
                                                ".$this->tables['users'].".payed_page AS user_payed_page
                                         FROM ".$this->tables['users']."
                                         LEFT JOIN ".$this->tables['tarifs']." ON ".$this->tables['users'].".id_tarif = ".$this->tables['tarifs'].".id
                                         WHERE ".$this->tables['users'].".id = ?", $this->id_respondent_user);
                $agency_info = [];
                $payed_page = !empty($respondent_user_info) ? ($respondent_user_info['user_payed_page'] == 1) : false;
                if(!empty($respondent_user_info['id_agency'])){
                    $this->id_respondent_agency = $respondent_user_info['id_agency'];
                    $agency_info = $db->fetch("SELECT ".$this->tables['agencies'].".email AS agency_email,
                                                      ".$this->tables['agencies'].".title AS agency_title,
                                                      IF(".$this->tables['agencies'].".email_consults != '',".$this->tables['agencies'].".email_consults,".$this->tables['agencies'].".email) AS agency_email_service,
                                                      IF(advert_phone!='',advert_phone,phones) AS agency_phone, 
                                                      ".$this->tables['managers'].".name AS  manager_name,
                                                      ".$this->tables['managers'].".email AS manager_email,
                                                      id_tarif AS agency_tarif,
                                                      payed_page AS agency_payed_page
                                               FROM ".$this->tables['agencies']." 
                                               LEFT JOIN ".$this->tables['managers']." ON ".$this->tables['agencies'].".id_manager = ".$this->tables['managers'].".id
                                               WHERE ".$this->tables['agencies']." .id = ?", $respondent_user_info['id_agency']);
                    if(!empty($sale_app_cost)) $this->data_array['sale_cost'] = $sale_app_cost;
                    if(!empty($agency_info)) $payed_page = ($payed_page || $agency_info['agency_payed_page'] == 1);
                }
            }
            
            if(!empty($respondent_user_info)) $this->respondents_array = array_merge($respondent_user_info,$agency_info);
            else $this->respondents_array = [];
            
            $this->data_array['visible_to_all'] = ((empty($this->id_respondent_user) || empty($this->respondents_array['user_tarif']) || (!$payed_page)) ? 1 : 2);
            
            $this->data_array['answers_amount'] = 0;
            $this->data_array['id_first_answer'] = 0;
            $this->data_array['id_best_answer'] = 0;
            
            //отправляем на модерацию
            $this->status = 2;
            $this->data_array['status'] = 2;
            
            $res =  $this->saveToDB();
            if(empty($res)) $ajax_result['ok'] = false;
			else {                
	            $ajax_result['ok'] = true;
	            $this->data_array = $this->getItem( $this->id );
	            //флаг что вопрос внесен с созданием пользователя
	            $this->data_array['user_creating'] = $user_creating;
	            $this->data_array['id'] = $this->id;
	        }
        }
        else return false;
    }
    
    /**
    * инициализируем вопрос по уже существующему
    * 
    * @param mixed $id - непустой id вопроса который уже есть
    */
    private function Init($id){
        global $db;
        $this->tables = Config::$sys_tables;
        $user_info = [];$agency_info = [];
        
        if(empty($id)) return false;
            
        //читаем информацию по самому вопросу
        $q_info = $db->fetch("SELECT ".$this->tables['consults'].".*,
                                     DATE_FORMAT(".$this->tables['consults'].".question_datetime,'%e %M %Y') AS question_datetime_formatted,
                                     ".$this->tables['consults_categories'].".title AS category_title,
                                     ".$this->tables['consults_categories'].".title_genitive AS category_title_genitive,
                                     ".$this->tables['consults_categories'].".priority,
                                     ".$this->tables['consults_categories'].".lifetime,
                                     ".$this->tables['consults_categories'].".code
                              FROM ".$this->tables['consults']." 
                              LEFT JOIN ".$this->tables['consults_categories']." ON ".$this->tables['consults'].".id_category = ".$this->tables['consults_categories'].".id
                              WHERE ".$this->tables['consults'].".id = ".$id);
        if(!empty($q_info)){
            $this->data_array = $q_info;
            $this->id = $id;
            $this->id_category = $q_info['id_category'];
            $this->data_array['id'] = $this->id;
            $this->id_respondent_user = $q_info['id_respondent_user'];
            $this->id_initiating_user = $q_info['id_initiating_user'];
            $this->status = $q_info['status'];
            $this->visible_to_all = $q_info['visible_to_all'];
            
                
        }else return false;
        
        //читаем информацию по пользователю которому был адресован вопрос
        if(!empty($this->id_respondent_user)){
            $respondent_user_info = $db->fetch("SELECT ".$this->tables['users'].".id AS id_user,
                                                id_agency,email AS user_email,
                                                phone AS user_phone,
                                                CONCAT(name,' ',lastname) AS user_full_name,
                                                id_tarif AS user_tarif,
                                                (id_tarif>0) AS user_is_specialist,
                                                ".$this->tables['tarifs'].".title AS user_tarif_title,
                                                login,
                                                id_agency
                                         FROM ".$this->tables['users']."
                                         LEFT JOIN ".$this->tables['tarifs']." ON ".$this->tables['users'].".id_tarif = ".$this->tables['tarifs'].".id
                                         WHERE ".$this->tables['users'].".id = ".$this->id_respondent_user);
                
                if(!empty($respondent_user_info['id_agency'])){
                    $this->id_respondent_agency = $respondent_user_info['id_agency'];
                    $agency_info = $db->fetch("SELECT ".$this->tables['agencies'].".email AS agency_email,
                                                      ".$this->tables['agencies'].".title AS agency_title,
                                                      IF(".$this->tables['agencies'].".email_consults != '',".$this->tables['agencies'].".email_consults,".$this->tables['agencies'].".email) AS agency_email_service,
                                                      IF(advert_phone!='',advert_phone,phones) AS agency_phone, 
                                                      ".$this->tables['managers'].".name AS  manager_name,
                                                      ".$this->tables['managers'].".email AS manager_email,
                                                      id_tarif AS agency_tarif
                                               FROM ".$this->tables['agencies']." 
                                               LEFT JOIN ".$this->tables['managers']." ON ".$this->tables['agencies'].".id_manager = ".$this->tables['managers'].".id
                                               WHERE ".$this->tables['agencies'].".id = ".$respondent_user_info['id_agency']);
                }
                if(!empty($respondent_user_info)) $this->respondents_array = array_merge($respondent_user_info,$agency_info);
        }
        return true;
    }
    
    public function Delete(){
        global $db;
        unset($this->data_array);
        unset($this->respondents_array);
        $this->status = "deleted";
        return $db->querys("DELETE FROM ".$this->tables['consults']." WHERE id = ".$this->id);
    }
    
    public function getItem( $id = false ){
        global $db;
        $item = $db->fetch("SELECT ".$this->tables['consults'].".*,
                                               DATE_FORMAT(".$this->tables['consults'].".question_datetime,'%e %M %Y') AS question_datetime_formatted,
                                               ".$this->tables['consults_categories'].".title AS category_title,
                                               ".$this->tables['consults_categories'].".title_genitive AS category_title_genitive,
                                               ".$this->tables['consults_categories'].".priority,
                                               ".$this->tables['consults_categories'].".lifetime,
                                               ".$this->tables['consults_categories'].".code
                                        FROM ".$this->tables['consults']." 
                                        LEFT JOIN ".$this->tables['consults_categories']." ON ".$this->tables['consults'].".id_category = ".$this->tables['consults_categories'].".id
                                        WHERE ".$this->tables['consults'].".id = ? ", 
                                        !empty( $this->id ) ? $this->id : $id 
        );
        return $item;
    }
    
    public function Remoderate($send_notifications = true){
        global $db;
        $db->querys("UPDATE ".$this->tables['consults']." SET visible_to_all = 2, status = 2 WHERE id = ".$this->id);
        if(!empty($send_notifications)) $this->sendNotifications();
    }
    
    /**
    * шлем уведомления о поступлении вопроса с модерации
    * 
    */
    public function sendNotifications(){
        global $db;
        //для отправки системных сообщений
        $messages = new Messages();
        switch(true){
            case (!empty($this->id_respondent_user) && empty($this->respondents_array['id_agency'])):
                $notify_info = array('user_email'=>$this->respondents_array['user_email'],
                                     'user_title'=>$this->respondents_array['user_full_name'],
                                     'user_id'=>$this->respondents_array['id_user']
                                    );
            break;
            //определяем агентство и ответственного менеджера
            
            case (!empty($this->id_respondent_agency)):
                $respondent_agency_admin = $db->fetch("SELECT id,CONCAT(name,lastname) AS full_name, email FROM ".$this->tables['users']." WHERE id_agency = ? AND agency_admin = 1",$this->id_respondent_agency);
                
                $notify_info = array('agency_id'=>$this->respondents_array['id_agency'],
                                     'agency_title'=>$this->respondents_array['agency_title'],
                                     'agency_email'=>$this->respondents_array['agency_email_service'],
                                     'user_email'=>$this->respondents_array['user_email'],
                                     'admin_user_title'=>(!empty($respondent_agency_admin['full_name'])?$respondent_agency_admin['full_name']:""),
                                     'user_title'=>$this->respondents_array['user_full_name'],
                                     'is_specialist'=>$this->respondents_array['user_is_specialist'],
                                     'manager_name'=>$this->respondents_array['manager_name'],
                                     'manager_email'=>$this->respondents_array['manager_email'],
                                     'user_id'=>$this->respondents_array['id_user']
                                     );
            break;
        }
        
        //если email неправильный, сразу выходим
        $q_lifetime = (int)$this->data_array['lifetime'];
        
        //время закрытости вопроса
        if($q_lifetime % 60 == 0) $q_lifetime = (floor($q_lifetime/60))." ч.";
        else $q_lifetime = (($q_lifetime/60 > 0)?(floor($q_lifetime/60)." ч."):("")).(($q_lifetime % 60 > 0)?(($q_lifetime % 60)." мин."):(""));
        
        //готовим данные для письма
        $data['inserted_id'] = $this->id;
        $data['q_title'] = $this->data_array['title'];
        $data['q_body'] = $this->data_array['question'];
        $data['q_lifetime'] = $q_lifetime;
        $data['q_category_title'] = $this->data_array['category_title'];
        $data['user_name'] = $this->data_array['name'];
        $data['user_email'] = $this->data_array['email'];
        $data['admin_user_name'] = (!empty($notify_info['admin_user_title'])?$notify_info['admin_user_title']:"");
        $data['manager_name'] = (!empty($notify_info['manager_name'])?explode(' ',$notify_info['manager_name'])[0]:"");
        $data['user_title'] = (!empty($notify_info['user_title'])?$notify_info['user_title']:"");
        $data['agency_title'] = (!empty($notify_info['agency_title'])?$notify_info['agency_title']:"");
        $data['host'] = "bsn.ru";
        
        $env = array(
            'url' => Host::GetWebPath(),
            'host' => Host::$host,
            'datetime' => $this->data_array['question_datetime'],
            'author' => $this->data_array['name'],
            'ID' => $this->id,
            'text' => $this->data_array['question'],
            'login' => $this->data_array['email'],
            'section' => $this->data_array['category_title']
        );
        
        //персональный вопрос
        if(!empty($this->id_respondent_user)){
            if(!empty($notify_info['user_email'])){
                $sended = $this->sendNewQuestionNotification($env,$data,"/modules/consults/templates/mail_personal_spec.html",$notify_info['user_title'],$notify_info['user_email'],true);
                $messages->send(45523,$notify_info['user_id'],'Новый вопрос #'.$data['inserted_id'].' в сервисе Конусльтант',0,1);
            }
            if(!empty($notify_info['agency_email'])){
                $sended = $this->sendNewQuestionNotification($env,$data,"/modules/consults/templates/mail_personal_agency.html",$notify_info['admin_user_title'],$notify_info['agency_email'],true);
                $messages->send(45523,$respondent_agency_admin['id'],'Новый вопрос #'.$data['inserted_id'].' в сервисе Конусльтант',0,1);
            }
            if(!empty($notify_info['manager_email']) && Validate::isEmail($notify_info['manager_email'])){
                $sended = $this->sendNewQuestionNotification($env,$data,"/modules/consults/templates/mail_manager.html",$notify_info['manager_name'],$notify_info['manager_email'],true);
            }
        }
        //отсылаем общее письмо, как раньше
        else{
            // данные пользователя для шаблона
            $letter_data = array('email'=>$this->data_array['email'], 'name'=>"", 'title'=>$this->data_array['title'], 'id'=>$this->id);
            $addresses = "";
            
            $env = array(
                'url' => Host::GetWebPath(),
                'host' => Host::$host,
                'datetime' => $this->data_array['question_datetime'],
                'author' => $this->data_array['name'],
                'ID' => $this->id,
                'text' => $this->data_array['question'],
                'login' => $this->data_array['email'],
                'section' => $this->data_array['category_title']
            );
            
            //отдельно шлем на web@bsn.ru
            $this->sendNewQuestionNotification($env,$letter_data,"/modules/consults/templates/mail_notify_specs.html","","",true);
            
            //получаем список зарегистрированных специалистов
            $reged_specialists = ConsultQFunctions::getSpecialistsEmails(true);
            if(!empty($reged_specialists)){
                foreach($reged_specialists as $k=>$i){
                    $letter_data = array('email'=>$this->data_array['email'], 'name'=>$i['name'], 'title'=>$this->data_array['title'], 'id'=>$this->id);
                    $this->sendNewQuestionNotification($env,$letter_data,"/modules/consults/templates/mail_notify_specs.html",$i['name'],$i['email'],true);
                }
            }
            
            //теперь отправляем письма специалистам, которые еще не зарегистрированы  --- пока убрано - так не надо
            $not_reged_specialists = ConsultQFunctions::getSpecialistsEmails(false);
            $not_reged_specialists = false;
            //
        }
    }
    
    /**
    * отправка письма о новом вопросе
    * 
    * @param mixed $env             - общие данные
    * @param mixed $letter_data     - данные письма
    * @param mixed $letter_template - шаблон для письма
    * @param mixed $reciever_name   - имя получателя
    * @param mixed $address         - адрес получателя
    * @param mixed $to_me           - дублирование на web@bsn.ru
    * @return bool
    */
    private function sendNewQuestionNotification($env,$letter_data,$letter_template,$reciever_name,$address,$to_me = false){
        Response::SetArray( "data", $letter_data );
        Response::SetArray('env', $env);
        
        $eml_tpl = new Template($letter_template);
        $html = $eml_tpl->Processing();

        if( !class_exists('Sendpulse') ) require("includes/class.sendpulse.php");
        //отправка письма
        $sendpulse = new Sendpulse( 'subscriberes' );
        $emails = [];
        if( Validate::isEmail( $address ) ) $emails[] = [ 'name' => '', 'email'=> $address ];
        //если указано, дублируем
        if( !empty( $to_me ) ) $emails[] = [ 'name' => '', 'email'=> 'web@bsn.ru' ];

        return $sendpulse->sendMail( (!empty($reciever_name)?$reciever_name.", с":"С").'оздан новый вопрос в сервисе Консультант '.Host::$host.' ID '.$this->id, $html, '', '', '', '', $emails );
    }
    
    /**
    * отправка оповещения пользователю о том что его вопрос прошел можерацию и размещен
    * 
    * @param mixed $id_answer      - id ответа
    * @param mixed $delayed_answer - признак вопроса ушедшего в ожидание
    */
    public function sendAskedUserNotification( $id_answer = false, $delayed_answer = false, $data_array = false ){
        $this->data_array = $this->getItem( $id_answer );
        //отправка письма пользователю, если он оставил email
        if(empty( $this->data_array['email'] ) || !Validate::isEmail( $this->data_array['email'] ) ) return false;
            
        // данные окружения для шаблона
        $env = array(
            'url' => Host::GetWebPath(),
            'host' => Host::$host,
            'datetime' => $this->data_array['question_datetime'],
            'author' => $this->data_array['name'],
            'ID' => $this->id,
            'title' => $this->data_array['title'],
            'text' => $this->data_array['question'],
            'login' => $this->data_array['email'],
            'category' => $this->data_array['code'],
            'section' => !empty( $this->data_array['category_title']) ? $this->data_array['category_title'] : '',
            'answer_id' => $id_answer,
            'delayed_answer' => $delayed_answer
        );
        Response::SetArray('env', $env);
        
        
        $eml_tpl = new Template('/modules/consults/templates/mail_notify_asker.html');     // формирование письма для юзера
        // формирование html-кода письма по шаблону
        $html = $eml_tpl->Processing();

        if( !class_exists('Sendpulse') ) require("includes/class.sendpulse.php");
        //отправка письма
        $sendpulse = new Sendpulse( 'subscriberes' );
        $emails = [
            [ 'name' => '',                         'email'=> 'web@bsn.ru' ],
            [ 'name' => $this->data_array['name'],  'email'=> $this->data_array['email'] ]
        ];
        if( empty( $id_answer ) ) $subject = 'Ваш вопрос «'.$this->id.'» в сервисе Консультант '.Host::$host.' прошел модерацию';
        else $subject = 'На Ваш вопрос в сервисе Консультант '.Host::$host.' пришел ответ';
        $correct_send = $sendpulse->sendMail( $subject, $html, '', '', '', '', $emails );

        Response::SetString('success', $correct_send ?? 'email' );       // отправка письма пользователю
    }
    
    /**
    * шлем уведомление модератору о новом вопросе
    * 
    */
    private function sendModeratorNotification($id_answer = false){
        global $db;
        $notify_info = $db->fetch("SELECT name,email
                                           FROM ".$this->tables['managers']."
                                           WHERE content_manager = 1");
        $notify_info['edit_url'] = $_SERVER['HTTP_HOST']."/admin/content/consults/edit/".$this->id."/";
        
        $notify_info['host'] = "bsn.ru";
        $notify_info['q_title'] = $this->data_array['title'];
        $notify_info['q_body'] = $this->data_array['question'];
        $notify_info['q_category_title'] = $this->data_array['category_title'];
        $notify_info['q_user_name'] = $this->data_array['name'];
        $notify_info['q_user_email'] = $this->data_array['email'];
        $notify_info['q_user_id'] = $this->id_initiating_user;
        
        $notify_info['responsed_user_name'] = (!empty($this->id_respondent_user)?$this->data_array['name']:"");
        $notify_info['responsed_agency_title'] = (!empty($this->id_respondent_agency)?$this->respondents_array['agency_title']:"");
        
        $notify_info['user_creating'] = (!empty($this->data_array['user_creating'])?$this->data_array['user_creating']:0);
        
        //если передана информация об ответе, это модерация ответа
        if(!empty($id_answer)){
            $answer_info =  $db->fetch("SELECT ".$this->tables['consults_answers'].".answer,
                                               ".$this->tables['consults_answers'].".date_in,
                                               ".$this->tables['users'].".id AS respondent_user_id,
                                               ".$this->tables['users'].".name AS respondent_user_title,
                                               IF(".$this->tables['agencies'].".id IS NOT NULL,".$this->tables['agencies'].".id,0) AS respondent_agency_id,
                                               IF(".$this->tables['agencies'].".id IS NOT NULL,".$this->tables['agencies'].".title,'') AS respondent_agency_title
                                        FROM ".$this->tables['consults_answers']." 
                                        LEFT JOIN ".$this->tables['users']." ON ".$this->tables['consults_answers'].".id_user = ".$this->tables['users'].".id
                                        LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['users'].".id_agency = ".$this->tables['agencies'].".id
                                        WHERE ".$this->tables['consults_answers'].".id = ?",$id_answer);
            if(!empty($answer_info)){
                $notify_info['answer'] = $answer_info['answer'];
                $notify_info['respondent_user_id'] = $answer_info['respondent_user_id'];
                $notify_info['respondent_user_title'] = $answer_info['respondent_user_title'];
                $notify_info['respondent_agency_id'] = $answer_info['respondent_agency_id'];
                $notify_info['respondent_agency_title'] = $answer_info['respondent_agency_title'];
                $notify_info['responce_datetime'] = $answer_info['date_in'];
                $notify_info['answer_edit_url'] = $_SERVER['HTTP_HOST']."/admin/content/consults/view/".$this->id."/edit/".$id_answer."/";
            }
        }
        
        if( !empty( $notify_info['email'] ) ){
            // формирование html-кода письма по шаблону
            Response::SetArray('data',$notify_info);
            //если вопрос на специалиста
            Response::SetBoolean('agent',(!empty($this->id_respondent_user) && !empty($this->respondents_array['user_tarif']) && $this->respondents_array['user_tarif']>0) );

            //если email корректный, отправляем письмо
            if(!empty($notify_info['email'])  && Validate::isEmail($notify_info['email'])){
                $eml_tpl = new Template('/modules/consults/templates/mail_content_manager.html');
                $html = $eml_tpl->Processing();

                if( !class_exists('Sendpulse') ) require("includes/class.sendpulse.php");
                //отправка письма
                $sendpulse = new Sendpulse( 'subscriberes' );
                $site = preg_replace('/^www\./','',$_SERVER['HTTP_HOST']);
                $subject = "Необходимо проверить ".(empty($notify_info['answer'])?"вопрос":"ответ")." на ".$site." - ID ".(empty($notify_info['answer'])?$this->id:$id_answer).", ".date('Y-m-d H:i:s');

                $emails = [
                    [ 'name' => '',  'email'=> 'web@bsn.ru' ],
                    [ 'name' => '',  'email'=> $notify_info['email'] ]
                ];
                $correct_send = $sendpulse->sendMail( $subject, $html, '', '', '', '', $emails );
            }
        }
        return true;
    }
    
    private function sendResponderNotification($id_answer){
        global $db;
        $answer_info = $db->fetch("SELECT ".$this->tables['consults_answers'].".id, 
                                          ".$this->tables['consults_answers'].".answer,
                                          ".$this->tables['users'].".email,
                                          ".$this->tables['users'].".name AS user_name
                                   FROM ".$this->tables['consults_answers']."
                                   LEFT JOIN ".$this->tables['users']." ON ".$this->tables['users'].".id = ".$this->tables['consults_answers'].".id_user
                                   WHERE ".$this->tables['consults_answers'].".id = ?",$id_answer);
        if(empty($answer_info['email'])) return false;
        // данные окружения для шаблона
        $env = array(
            'url' => Host::GetWebPath(),
            'host' => Host::$host,
            'user_title' => $answer_info['user_name'],
            'answer_id' => $id_answer
        );
        Response::SetArray('env', $env);
        
        $eml_tpl = new Template('/modules/consults/templates/mail_notify_responder.html');     // формирование письма для юзера
        // формирование html-кода письма по шаблону
        $html = $eml_tpl->Processing();

        //отправка письма
        if( !class_exists('Sendpulse') ) require("includes/class.sendpulse.php");
        $sendpulse = new Sendpulse( 'subscriberes' );

        $subject = 'Ваш ответ ID '.$answer_info['id'].' в сервисе Консультант '.Host::$host.' прошел модерацию';

        $emails = [
            [ 'name' => '',  'email'=> 'web@bsn.ru' ],
            [ 'name' => $answer_info['user_name'],  'email'=> $answer_info['email'] ]
        ];
        $correct_send = $sendpulse->sendMail( $subject, $html, '', '', '', '', $emails );
        Response::SetString( 'success', $correct_send ?? 'email' );       // отправка письма пользователю
    }
    
    /**
    * добавляем ответ к вопросу
    * 
    * @param mixed $answer_data - массив, содержащий ответ и id пользователя который ответил
    * 'answer' - текст ответа, 'id_user' - id пользователя, 'is_draft' - признак черновика
    * @return mixed
    */
    public function addAnswer($answer_data){
        global $db;
        if(empty($answer_data['answer'])) return false;
        //если это черновик, отмечаем
        $status = (!empty($answer_data['is_draft'])?5:2);
        $this->data_array['answers_amount']++;
        //если это черновик, проверяем, вдруг уже есть
        if($status == 5 && !empty($answer_data['id'])){
            $exists = $db->fetch("SELECT id FROM ".$this->tables['consults_answers']." WHERE id = ?",$answer_data['id']);
            if(!empty($exists)) return false;
        }
        $res = $db->querys("INSERT INTO ".$this->tables['consults_answers']." (status,answer,id_parent,date_in,id_user) VALUES (?,?,?,NOW(),?)",$status,$answer_data['answer'],$this->id,$answer_data['id_user']);
        $new_id = $db->insert_id;
        //шлем оповещение модератору о новом ответе, если это не черновик
        if($status != 5) $this->sendModeratorNotification($new_id);
        return (empty($res)?false:$new_id);
    }
    
    /**
    * убираем ответ в архивный
    * 
    * @param mixed $id
    */
    public function updateAnswer( $answer_data ) {
        global $db;
        $id = $answer_data['id'];
        if( empty( $id ) || empty( $answer_data['id_parent'] ) ) return false;
        $old_status = $db->fetch( "SELECT status FROM ".$this->tables['consults_answers']." WHERE id = ?",$id);
        if( empty( $old_status ) ) return false;
        $old_status = $old_status['status'];
        //уходит из опубликованных
        if( $answer_data['status'] != 1 && $old_status == 1 ) {
            if($this->data_array['id_best_answer'] == $id)  $this->data_array['id_best_answer'] = 0;
            if($this->data_array['id_first_answer'] == $id){
                $this->data_array['id_first_answer'] = $db->fetch("SELECT id FROM ".$this->tables['consults_answers']." WHERE id_parent = ? AND id != ? ORDER BY date_in ASC",$this->id,$id);
                $this->data_array['id_first_answer'] = empty($this->data_array['id_first_answer'])?0:$this->data_array['id_first_answer']['id'];
            }
            $this->data_array['answers_amount']--;
            $answer_data['date_in'] = date("Y-m-d H:i:s",time());
        }
        //идет в опубликованные
        elseif($answer_data['status'] == 1 && $old_status != 1){
            $first_answer = $db->fetch("SELECT id FROM ".$this->tables['consults_answers']." WHERE id_parent = ? ORDER BY date_in ASC",$this->id);
            if(!empty($first_answer) && $first_answer['id'] == $id) $this->data_array['id_first_answer'] = $id;
            $this->data_array['answers_amount']++;
            
            if(!empty($answer_data['id'])){
                $this->sendAskedUserNotification($answer_data['id']);
                $this->sendResponderNotification($answer_data['id']);
            }
            unset($answer_data['date_in']);
        }
        //идет на модерацию
        elseif($answer_data['status'] == 2 && $old_status != 2 && $old_status != 1){
            $answer_data['date_in'] = date("Y-m-d H:i:s",time());
        }
        
        
        //сохраняем сам ответ
        $res = $db->updateFromArray($this->tables['consults_answers'],$answer_data,'id');
        
        if($answer_data['status'] == 2 && (empty($old_status) || $old_status !=2) ){
            $this->sendModeratorNotification($answer_data['id']);
        }
        
        $this->refreshAnswersAmount();
        return $res;
    }
    
    public function deleteAnswer($id){
        global $db;
        if(empty($id)) return false;
        //если уходит/помещается в опубликованные, корректируем значения
        
        if($this->data_array['id_best_answer'] == $id)  $this->data_array['id_best_answer'] = 0;
        if($this->data_array['id_first_answer'] == $id){
            $this->data_array['id_first_answer'] = $db->fetch("SELECT id FROM ".$this->tables['consults_answers']." WHERE id_parent = ? AND id != ? ORDER BY date_in ASC",$this->id,$id);
            $this->data_array['id_first_answer'] = empty($this->data_array['id_first_answer'])?0:$this->data_array['id_first_answer']['id'];
        }
        $this->data_array['answers_amount']--;
        $this->saveToDB();
        $res = $db->querys("DELETE FROM ".$this->tables['consults_answers']." WHERE id = ?",$id);
        return $res;
    }
    
    public function getFirstAnswer(){
        global $db;
        if(empty($this->data_array['answers_amount']) || empty($this->data_array['id_first_answer'])) return false;
        $res = $db->fetch("SELECT ".$this->tables['consults_answers'].".*,".$this->tables['users'].".name AS user_name 
                           FROM ".$this->tables['consults_answers']."
                           LEFT JOIN ".$this->tables['users']." ON ".$this->tables['consults_answers'].".id_user = ".$this->tables['users'].".id
                           WHERE ".$this->tables['consults_answers'].".id = ? and status = 1",$this->data_array['id_first_answer']);
        return $res;
    }
    
    public function getBestAnswer(){
        global $db;
        if(empty($this->data_array['answers_amount']) || empty($this->data_array['id_best_answer'])) return false;
        $res = $db->fetch("SELECT ".$this->tables['consults_answers'].".*,".$this->tables['users'].".name AS user_name
                           FROM ".$this->tables['consults_answers']."
                           LEFT JOIN ".$this->tables['users']." ON ".$this->tables['consults_answers'].".id_user = ".$this->tables['users'].".id
                           WHERE ".$this->tables['consults_answers'].".id = ? and status = 1",$this->data_array['id_best_answer']);
        return $res;
    }
    
    public function makeBestAnswer($answer_id){
        global $db;
        if(empty($this->data_array['answers_amount']) || empty($answer_id)) return false;
        $answer_info = $db->fetch("SELECT id_parent FROM ".$this->tables['consults_answers']." WHERE id = ?",$answer_id);
        if(empty($answer_info) || $answer_info['id_parent'] != $this->id) return false;
        $res = $db->querys("UPDATE ".$this->tables['consults']." SET ".$this->tables['consults'].".id_best_answer = ? WHERE id = ?",$answer_id,$this->id);
        return $res;
    }
    
    public function refreshAnswersAmount(){
        global $db;
        $answers_amount = $db->fetch("SELECT COUNT(*) AS amount FROM ".$this->tables['consults_answers']." WHERE id_parent = ? AND status = 1",$this->id);
        if(empty($answers_amount)) return false;
        $answers_amount = (empty($answers_amount['amount'])?0:$answers_amount['amount']);
        $this->data_array['answers_amount'] = $answers_amount;
        $first_answer = $db->fetch("SELECT id FROM ".$this->tables['consults_answers']." WHERE id_parent = ? ORDER BY date_in ASC",$this->id);
        $first_answer = (empty($first_answer)?0:$first_answer['id']);
        $res = $db->querys("UPDATE ".$this->tables['consults']." SET answers_amount = ?, id_first_answer = ? WHERE id = ?",$answers_amount,$first_answer,$this->id);
        return $res;
    }
    
    /**
    * список опубликованных ответов на вопрос
    * 
    * @param mixed $all_statuses - флаг, выводить ли все статусы(для админки) или только опубликованные (для морды)
    * @param mixed $sortby - сортировка (по умолчанию - сначала лучший, потом по дате DESC)
    */
    public function getAnswersList($all_statuses = false,$sortby = false, $id_user = false, $viewed_by_owner = false){
        global $db, $auth;
        $sortby = (!empty($sortby)?", ".$sortby:", date_in ASC");
        $where = [];
        if(!empty($this->id)) $where[] = $this->tables['consults_answers'].".id_parent = ".$this->id;
        if(empty($all_statuses)) $where[] = $this->tables['consults_answers'].".status = 1 ";
        if(!empty($id_user)) $where[] = $this->tables['consults'].".id_initiating_user =  ".$id_user;
        if(!empty($viewed_by_owner)) $where[] = $this->tables['consults_answers'].".viewed_by_owner = 2 ";
        $where = implode(" AND ", $where) ;
        $res = $db->fetchall("SELECT ".$this->tables['consults_answers'].".*,
                                     DATE_FORMAT(".$this->tables['consults_answers'].".date_in,'%e %M %Y %h:%i:%s') AS date_in_formatted,
                                     CASE
                                        WHEN ".$this->tables['consults_answers'].".status = 1 THEN 'Опубликован'
                                        WHEN ".$this->tables['consults_answers'].".status = 2 THEN 'На модерации'
                                        WHEN ".$this->tables['consults_answers'].".status = 3 THEN 'Не прошел модерацию'
                                        WHEN ".$this->tables['consults_answers'].".status = 4 THEN 'В архиве'
                                     END AS status_title,
                                     ".(!empty($this->data_array['id_best_answer']) ? "IF(".$this->tables['consults_answers'].".id = ".$this->data_array['id_best_answer'].",1,0) AS is_best_answer," : "")."
                                     ".$this->tables['users'].".name AS user_name,
                                     ".$this->tables['users'].".id AS user_id,
                                     ".$this->tables['users'].".sex,
                                     ".$this->tables['users'].".avatar_color,
                                     CONCAT_WS('/','".Config::$values['img_folders']['agencies']."','big',LEFT(".$this->tables['users_photos'].".name,2)) as user_photo_folder,
                                     ".$this->tables['users_photos'].".name as user_photo,
                                     ".$this->tables['agencies'].".title AS agency_title,
                                     ".$this->tables['agencies'].".chpu_title AS agency_chpu,
                                     ".$this->tables['agencies'].".id AS agency_id,
                                     IF( ".$auth->id." = 0 OR ".$auth->id." = ".$this->tables['consults_answers'].".id_user OR v.voted = 1,0,1) AS can_vote,
                                     v.rating AS rating,
                                     v.voted AS voted_already,
                                     v.voted_total,
                                     ".$this->tables['consults'].".question,
                                     ".$this->tables['consults_categories'].".code
                              FROM ".$this->tables['consults_answers']."
                              LEFT JOIN (SELECT id_parent,(SUM(vote_for)-SUM(vote_against)) AS rating, COUNT(*) as voted_total, ".(!empty($auth->id)?"SUM(id_user = ".$auth->id.") AS voted":"0 AS voted")." FROM ".$this->tables['consults_answers_votings']." GROUP BY id_parent) v ON v.id_parent = ".$this->tables['consults_answers'].".id
                              RIGHT JOIN ".$this->tables['consults']." ON ".$this->tables['consults'].".id = ".$this->tables['consults_answers'].".id_parent
                              RIGHT JOIN ".$this->tables['consults_categories']." ON ".$this->tables['consults'].".id_category = ".$this->tables['consults_categories'].".id
                              LEFT JOIN ".$this->tables['users']." ON ".$this->tables['consults_answers'].".id_user = ".$this->tables['users'].".id
                              LEFT JOIN ".$this->tables['users_photos']." ON ".$this->tables['users_photos'].".id_parent=".$this->tables['users'].".id
                              LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['users'].".id_agency = ".$this->tables['agencies'].".id
                              WHERE ".$where."
                              GROUP BY ".$this->tables['consults_answers'].".id
                              ".(!empty($this->data_array['id_best_answer']) ? "ORDER BY ".$this->tables['consults_answers'].".id = ".$this->data_array['id_best_answer']." DESC".$sortby : "" ),
                              false);
        return $res;
    }
    
    /**
    * проверяем, может ли данный пользователь ответить на конкретный вопрос
    * 
    * @param mixed $id - id пользователя
    */
    public function checkIfCanAnswerThis($id_user){
        global $db;
        
        $user_info = $db->fetch("SELECT id,user_activity, id_tarif FROM ".$this->tables['users']." WHERE id = ?",$id_user);
        //если пользователя нет, или это не юрист, или нет нужного тарифа
        if(empty($user_info) || $user_info['user_activity'] != 2 || empty($user_info['id_tarif']) || $user_info['id_tarif'] != 4) return false;
        //нельзя отвечать еще раз в ту же ветку
        $answered_already = $db->fetch("SELECT id FROM ".$this->tables['consults_answers']." WHERE id_parent = ? AND status IN (1,2,5) AND id_user = ?",$this->id,$id_user);
        if(!empty($answered_already) && !empty($answered_already['id'])) return false;
        
        return true;
    }
    
    /**
    * достаем парамеры для шаблона
    * 
    * @param mixed $fields_list - если нужны какие-то особые, даем список полей
    */
    public function getTemplateInfo($fields_list = false){
        $template_info = [];
        if(empty($fields_list)){
            $template_info['id'] = $this->id;
            $template_info['title'] = $this->data_array['title'];
            $template_info['id_initiating_user'] = $this->data_array['id_initiating_user'];
            $template_info['question'] = $this->data_array['question'];
            $template_info['name'] = $this->data_array['name'];
            $template_info['answers_amount'] = $this->data_array['answers_amount'];
            $template_info['question_datetime'] = $this->data_array['question_datetime'];
            $template_info['question_datetime_formatted'] = $this->data_array['question_datetime_formatted'];
            $template_info['rating'] = $this->data_array['rating'];
            $template_info['category_title'] = $this->data_array['category_title'];
            $template_info['category_title_genitive'] = $this->data_array['category_title_genitive'];
            $template_info['category_code'] = $this->data_array['code'];
        }else{
            foreach($fields_list as $key=>$item){
                $template_info[$item] = $this->data_array[$item];
            }
        }
        return $template_info;
    }
    
    public function getRespondentInfo($fields_list = false){
        if(empty($this->id_respondent_user)) return false;
        $info = [];
        if(empty($fields_list)){
            $info['user_id'] = $this->id_respondent_user;
            $info['user_email'] = $this->respondents_array['user_email'];
            $info['user_full_name'] = $this->respondents_array['user_full_name'];
            $info['user_tarif_title'] = $this->respondents_array['user_tarif_title'];
            if(!empty($this->respondents_array['id_agency'])){
                $info['agency_id'] = $this->respondents_array['id_agency'];
                $info['agency_email_service'] = $this->respondents_array['agency_email_service'];
                $info['agency_title'] = $this->respondents_array['agency_title'];
            }
        }else{
            foreach($fields_list as $key=>$item){
                $info[$item] = $this->data_array[$item];
            }
        }
        return $info;
    }
    
    public function toShared(){
        global $db;
        $res = $db->querys("UPDATE ".$this->tables['consults']." SET visible_to_all = 1 WHERE id = ?",$this->id);
        if(!empty($res)){
            $this->data_array['visible_to_all'] = 1;
            $this->visible_to_all = 1;
        }
        return $res;
    }
    
    public function fromShared(){
        global $db;
        if($this->status == 2) $res = $db->querys("UPDATE ".$this->tables['consults']." SET visible_to_all = 2 WHERE id = ?",$this->id);
        if($res) $this->data_array['visible_to_all'] = 2;
        return $res;
    }
    
    public function toArchive(){
        global $db;
        $res = $db->querys("UPDATE ".$this->tables['consults']." SET status = 5 WHERE id = ?",$this->id);
        if($res) $this->data_array['status'] = 5;
        return $res;
    }
    
    public function toModer(){
        global $db;
        if($this->status == 2) $res =  $db->querys("UPDATE ".$this->tables['consults']." SET status = 4, `question_datetime` = '0000-00-00 00:00:00', visible_to_all = 2 WHERE id = ".$this->id);
        if($res){
            $this->data_array['status'] = 2;
            $this->data_array['question_datetime'] = '0000-00-00 00:00:00';
            $this->data_array['visible_to_all'] = 2;
        }
        return $res;
    }
    
    public function toPublished(){
        global $db;
        return $db->querys("UPDATE ".$this->tables['consults']." SET status = 1 WHERE id = ".$this->id);
    }
    
    public function toUser($to_user_id){
        global $db;
        $res = $db->querys("UPDATE ".$this->tables['consults']." SET id_responded_user = ?,visible_to_all = ? WHERE id = ?",$to_user_id,2,$this->id);
        if($res){
            $this->id_respondent_agency = $to_user_id;
            $this->data_array['id_responded_user'] = $to_user_id;
            $this->visible_to_all = 2;
            $this->data_array['visible_to_all'] = 2;
        }
        return $res;
    }
    
    public function updateFromMapping($mapping){
        foreach($mapping as $key=>$item){
            if(isset($this->data_array[$key])) $this->data_array[$key] = $mapping[$key]['value'];
        }
    }
    
    public function returnToMapping(&$mapping){
        foreach($this->data_array as $key=>$field){
            if(!empty($mapping[$key])) $mapping[$key]['value'] = $field;
        }
    }
    
    /**
    * апдейтим вопрос в базе
    * 
    * @param mixed $refresh_date - записать дату модерации
    */
    public function saveToDB($refresh_date = false){
        global $db;
        
        $new_q = empty($this->id);
        
        $insert_app_info = array('id'=>$this->id,
                                 'status'=>$this->data_array['status'],
                                 'visible_to_all'=>$this->data_array['visible_to_all'],
                                 'title'=>(!empty($this->data_array['title'])?$this->data_array['title']:""),
                                 'question'=>(!empty($this->data_array['question'])?$this->data_array['question']:""),
                                 'name'=>$this->data_array['name'],
                                 'email'=>$this->data_array['email'],
                                 'id_initiating_user'=>$this->data_array['id_initiating_user'],
                                 'id_category'=>$this->data_array['id_category'],
                                 'id_respondent_user'=>$this->data_array['id_respondent_user'],
                                 'answers_amount'=>$this->data_array['answers_amount'],
                                 'id_first_answer'=>$this->data_array['id_first_answer'],
                                 'id_best_answer'=>$this->data_array['id_best_answer']
                                 );
        if(empty($new_q)) $res = $db->updateFromArray($this->tables['consults'],$insert_app_info,'id');
        else $res = $db->insertFromArray($this->tables['consults'],$insert_app_info,'id');
        
        if($res){
            if(!empty($new_q)){
                $this->id = $db->insert_id;
                $this->data_array['id'] = $this->id;
                $this->id_respondent_user = $this->data_array['id_respondent_user'];
                $db->querys("UPDATE ".$this->tables['consults']." SET `question_datetime` = NOW() WHERE id = ?",$this->id);
            }
            if(!empty($refresh_date)) $db->querys("UPDATE ".$this->tables['consults']." SET `moderation_datetime` = NOW() WHERE id = ?",$this->id);
            $this->status = $this->data_array['status'];
            $this->visible_to_all = $this->data_array['visible_to_all'];
        } 
        return $res;
    }
    
    /**
    * собираем строку описания вопроса
    * 
    * @return mixed
    */
    public function getDescription(){
        global $db;
        switch(true){
            case $this->id_respondent_user == 0:
                $description['text'] = "Общий вопрос  #".$this->id." раздела ".$this->data_array['category_title'];
                break;
            case !empty($this->id_respondent_agency):
                $description['text'] = "Вопрос  #".$this->id." раздела ".$this->data_array['category_title']." для специалистов компании #".$this->respondents_array['agency_title'];
                break;
            case !empty($this->id_respondent_user):
                $description['text'] = "Вопрос  #".$this->id." раздела ".$this->data_array['category_title']." для специалиста #".$this->id_respondent_user." ".$this->respondents_array['user_name'];
                break;
        }
        $description['type'] = $this->data_array['category_title'];
        return $description;
    }
    
    /**
    * дергаем поле из данных вопроса
    * 
    * @param mixed $attr_name - название поля
    * @return mixed
    */
    public function getAttr($attr_name){
        return (empty($this->data_array[$attr_name])?"":$this->data_array[$attr_name]);
    }
    
    /**
    * дергаем поле из данных по хозяевам вопроса
    * 
    * @param mixed $attr_name
    * @return mixed
    */
    public function getOwnersAttr($attr_name){
        return (empty($this->respondents_array[$attr_name])?"":$this->respondents_array[$attr_name]);
    }
    
    /**
    * проверяем что вопрос пришел клиенту в рабочее время. если нет - перемещаем в ожидающие запуска
    * 
    */
    public function checkWorkTime(){
        //если не в агентство или в агрегатор - считаем время рабочим
        if(!empty($this->id_respondent_agency)){
            global $db;
            //проверяем что для агентства вообще указано время, если не указано, считаем 24/7
            $agency_has_time = $db->fetch("SELECT id FROM ".$this->tables['agencies_opening_hours']." WHERE id_agency = ".$this->id_respondent_agency);
            if(!empty($agency_has_time)){
                $agency_in_time = $db->fetch("SELECT ".$this->tables['agencies'].".id
                                              FROM ".$this->tables['agencies']."
                                              LEFT JOIN ".$this->tables['agencies_opening_hours']." ON ".$this->tables['agencies'].".id = ".$this->tables['agencies_opening_hours'].".id_agency
                                              WHERE ".$this->tables['agencies'].".id = ".$this->id_respondent_agency." AND
                                                    ".$this->tables['agencies_opening_hours'].".day_num = WEEKDAY(NOW())+1 AND
                                                    ".$this->tables['agencies_opening_hours'].".questions_processing = 1 AND 
                                                    ".$this->tables['agencies_opening_hours'].".`begin`<TIME_FORMAT(NOW(),'%H:%i:%s') AND
                                                    ".$this->tables['agencies_opening_hours'].".`end`>TIME_FORMAT(NOW(),'%H:%i:%s')")['id'];
                //если это не агрегатор и мы не попали во время работы, отправляем вопрос в "ждущие опубликования"
                if(empty($agency_in_time)){
                    //если у агентства есть тариф, делаем ожидание
                    if($this->respondents_array['agency_tarif'] != 0){
                        $this->status = 4;
                        $this->data_array['status'] = 4;
                    }else{
                        $this->status = 1;
                        $this->data_array['status'] = 1;
                        $this->toShared();
                    }
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
    * проверяем что вопрос пришел клиенту в рабочие сутки. если нет - перемещаем в ожидающие запуска
    * 
    */
    public function checkWorkDay(){
        //если не в агентство или в агрегатор - считаем время рабочим
        if(!empty($this->id_respondent_agency)){
            global $db;
            //проверяем что для агентства вообще указано время, если не указано, считаем 24/7
            $agency_has_time = $db->fetch("SELECT id FROM ".$this->tables['agencies_opening_hours']." WHERE id_agency = ".$this->id_respondent_agency);
            if(!empty($agency_has_time)){
                $agency_in_time = $db->fetch("SELECT ".$this->tables['agencies'].".id
                                              FROM ".$this->tables['agencies']."
                                              LEFT JOIN ".$this->tables['agencies_opening_hours']." ON ".$this->tables['agencies'].".id = ".$this->tables['agencies_opening_hours'].".id_agency
                                              WHERE ".$this->tables['agencies'].".id = ".$this->id_respondent_agency." AND
                                                    ".$this->tables['agencies_opening_hours'].".questions_processing = 1 AND
                                                    ".$this->tables['agencies_opening_hours'].".day_num = WEEKDAY(NOW())+1")['id'];
                //если это не агрегатор и мы не попали во время работы, отправляем вопрос в "ждущие опубликования"
                if(empty($agency_in_time)){
                    $this->status = 4;
                    $this->data_array['status'] = 4;
                    return false;
                }
            }
        }
        return true;
    }
    /**
    * публикуем вопрос из админки
    * 
    */
    public function publishQuestion(){
        global $db;
        
        if($this->checkWorkDay() && $this->checkWorkTime()) $this->sendNotifications();
        
        $this->sendAskedUserNotification(false,true);
        return $this->saveToDB(true);
    }
    
    /**
    * заход на карточку - обновляем поле в базе
    * 
    */
    public function visitQuestionPage(){
        global $db;
        $res = $db->querys("UPDATE ".$this->tables['consults']." SET views = views + 1 WHERE id = ?",$this->id);
        return $res;
    }
}

/**
*   Класс для списка заявок
*/
class ConsultQuestionList {
}

/**
* класс для всяких нужных методов для заявок
*/
abstract class ConsultQFunctions{
    const consultant_url = "/service/consultant/";
    /**
    * просматриваем ожидающие публикации, достаем нужные
    * 
    * @param mixed $days - флаг того как смотрим - с учетом времени работы(false) или только с учетом суток(true)
    */
    public static function publishWaiting($days = false){
        global $db;
        $sys_tables = Config::$sys_tables;
        //читаем ожидающие публикации у которых подошло время
        $waiting_list = $db->fetchall("SELECT ".$sys_tables['consults'].".id
                                       FROM ".$sys_tables['consults']."
                                       LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['consults'].".id_respondent_user = ".$sys_tables['users'].".id
                                       LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                       LEFT JOIN ".$sys_tables['agencies_opening_hours']." ON ".$sys_tables['agencies'].".id = ".$sys_tables['agencies_opening_hours'].".id_agency
                                       WHERE ".$sys_tables['consults'].".status = 4 AND
                                             ".$sys_tables['agencies_opening_hours'].".day_num = WEEKDAY(NOW())+1 AND questions_processing = 1
                                             ".(empty($days)?" AND ".$sys_tables['agencies_opening_hours'].".`begin`<TIME_FORMAT(NOW(),'%H:%i:%s') AND
                                             ".$sys_tables['agencies_opening_hours'].".`end`>TIME_FORMAT(NOW(),'%H:%i:%s')":"")
                                     );
        $res = true;
        //публикуем готовые
        foreach($waiting_list as $key=>$item){
            $new_app = new ConsultQuestion($item['id'],null,null);
            $res = $res && $new_app->toPublished();
            $new_app->sendNotifications();
            unset($new_app);
        }
        return $res;
    }
    
    public static function validateAddFormParams($params){
        $errors = [];
        // проверяем логин заполненность
        if(empty($params['name'])) $errors['name'] = 'Не допускается пустое значение';
        if(empty($params['text'])) $errors['text'] = 'Не допускается пустое значение';
        if(empty($params['category'])) $errors['category'] = 'Не допускается пустое значение';
        //if(!empty($params['responder']) && !ConsultQFunctions::checkIfCanAnswer($params['responder'])) $errors['responder'] = 'Этот специалист не может ответить, вопрос будет перенесе в общий пул';
        //else
        if(!empty($field['mail']) && !Validate::isEmail($params['email'])) $errors['email'] = 'ошибка, неверный email';
        return $errors;
    }
    
    /**
    * список специалистов-консультантов
    * 
    * @param mixed $reged - флаг, false-выбираем незарегистрированных
    */
    public static function getSpecialistsEmails($reged = false){
        global $db;
        $sys_tables = Config::$sys_tables;
        /*
        $list = $db->fetchall("SELECT ".(!empty($reged)?$sys_tables['consults_members'].".email,".$sys_tables['users'].".name":$sys_tables['consults_members'].".email,".$sys_tables['consults_members'].".name")."
                               FROM ".$sys_tables['consults_members']."
                               LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['users'].".email = ".$sys_tables['consults_members'].".email
                               WHERE ".$sys_tables['consults_members'].".email!='' and ".$sys_tables['users'].".email IS ".(!empty($reged)?"NOT ":"")."NULL
                               GROUP BY ".$sys_tables['users'].".id
                               UNION SELECT email,name FROM ".$sys_tables['users']." WHERE user_activity = 2");
        */
        
        $list = $db->fetchall("SELECT email,name FROM ".$sys_tables['users']." WHERE user_activity = 2");
        return $list;
    }
    
    /**
    * список пар ответ-вопрос для выбранного пользователя-специалиста
    * 
    * @param mixed $spec_id
    * @param mixed $sortby
    */
    public static function getSpecialistAnswers($spec_id, $sortby = false, $from = 0, $count = 0){
        global $db;
        global $auth;
        $limits = (!empty($count)?" LIMIT ".(!empty($from)?$from.", ":"").$count:"");
        $sys_tables = Config::$sys_tables;
        //если такого пользователя нет, выходим
        if( empty($spec_id) || !Common::getUserById($spec_id) ) return false;
        $answers_list = $db->fetchall("SELECT ".$sys_tables['consults_answers'].".*,
                                              DATE_FORMAT(".$sys_tables['consults_answers'].".date_in,'%e %M %Y %h:%i:%s') AS answer_date_in_formatted,
                                              ".$sys_tables['consults'].".id AS question_id,
                                              CONCAT('".ConsultQFunctions::consultant_url."',".$sys_tables['consults_categories'].".code,'/',".$sys_tables['consults'].".id,'/') AS question_url,
                                              IF(".$sys_tables['consults'].".title = '',".$sys_tables['consults'].".question,".$sys_tables['consults'].".title) AS question_text,
                                              IF(".$sys_tables['consults_answers'].".id = ".$sys_tables['consults'].".id_best_answer,1,0) AS is_best_answer,
                                             ".$sys_tables['users'].".name AS user_name,
                                             ".$sys_tables['users'].".id AS user_id,
                                             ".$sys_tables['users'].".sex,
                                             ".$sys_tables['users'].".avatar_color,
                                             CONCAT_WS('/','".Config::$values['img_folders']['agencies']."','big',LEFT(".$sys_tables['users_photos'].".name,2)) as user_photo_folder,
                                             ".$sys_tables['users_photos'].".name as user_photo,
                                             ".$sys_tables['agencies'].".title AS agency_title,
                                             ".$sys_tables['agencies'].".chpu_title AS agency_chpu,
                                             ".$sys_tables['agencies'].".id AS agency_id,
                                              DATE_FORMAT(".$sys_tables['consults_answers'].".date_in,'%e.%m.%y') AS date_in_formatted,
                                              IF( ".$auth->id." = 0 OR ".$auth->id." = ".$sys_tables['consults_answers'].".id_user OR v.voted = 1,0,1) AS can_vote,
                                              v.rating AS rating,
                                              v.voted AS voted_already,
                                              CONCAT('/service/consultant/',".$sys_tables['consults_categories'].".code,'/',".$sys_tables['consults'].".id,'/') AS question_url,
                                              IF(".$sys_tables['consults'].".title != '',".$sys_tables['consults'].".title,".$sys_tables['consults'].".question) AS question_title,
                                              DATE_FORMAT(".$sys_tables['consults'].".question_datetime,'%e %b %Y, %H:%i') as question_normal_date,
                                              ".$sys_tables['consults'].".name AS question_author_info,
                                              ".$sys_tables['consults'].".answers_amount,
                                              ".$sys_tables['consults_categories'].".code AS category_url
                                              
                                       FROM ".$sys_tables['consults_answers']."
                                       LEFT JOIN ".$sys_tables['consults']." ON ".$sys_tables['consults'].".id = ".$sys_tables['consults_answers'].".id_parent
                                       LEFT JOIN (SELECT id_parent,(SUM(vote_for)-SUM(vote_against)) AS rating, ".(!empty($auth->id)?"SUM(id_user = ".$auth->id.") AS voted":"0 AS voted")." FROM ".$sys_tables['consults_answers_votings']." GROUP BY id_parent) v ON v.id_parent = ".$sys_tables['consults_answers'].".id
                                       LEFT JOIN ".$sys_tables['consults_categories']." ON ".$sys_tables['consults'].".id_category = ".$sys_tables['consults_categories'].".id
                                       LEFT JOIN ".$sys_tables['users']." ON ".$sys_tables['consults_answers'].".id_user = ".$sys_tables['users'].".id
                                       LEFT JOIN ".$sys_tables['users_photos']." ON ".$sys_tables['users_photos'].".id_parent=".$sys_tables['users'].".id
                                       LEFT JOIN ".$sys_tables['agencies']." ON ".$sys_tables['users'].".id_agency = ".$sys_tables['agencies'].".id
                                       WHERE ".$sys_tables['consults_answers'].".id_user = ? AND ".$sys_tables['consults_answers'].".status = 1
                                       ".(!empty($sortby)?" ORDER BY ".$sortby:"
                                       ").$limits."",false,$spec_id);
        return $answers_list;
    }
    
    public static function makeWhereClause($clauses){
        $result = [];
        if(!is_array($clauses)) return '';
        foreach($clauses as $field=>$values){
            if(empty($clauses[$field]['checked'])){
                if(strpos($field,'#')) $field = substr($field,0,strpos($field,'#'));
                $result[] = ConsultQFunctions::getClause($field, $values, $clauses);
            }
        }
        
        return implode(' AND ', $result);
    }
    
    private static function getClause($field, $values, &$clauses){
        global $db;
        $fld_table = (!empty($values['tablename'])?$values['tablename']:$this->tables['applications']);
        $fld = empty($fld_table) ? $field : $fld_table.".`".$field."`";
        $result = $or_resullt = "";
        if(empty($values['checked'])){
            if(isset($values['value'])) $result = $fld." = ".$db->quoted($values['value']);
            elseif(isset($values['not_value'])) $result = $fld." != ".$db->quoted($values['not_value']);
            elseif(isset($values['set']) || isset($values['not_set'])) {
                $arr = [];  
                if(isset($values['not_set'])) $values['set'] = $values['not_set'];
                if(!is_array($values['set'])) $values['set'] = explode(',',$values['set']);
                foreach($values['set'] as $item) 
                    $arr[] = $db->quoted($item);
                $result = !empty($arr)?$fld.(isset($values['not_set'])?" NOT ":"")." IN (" . implode(',',$arr) . ')':"";
            }
            else {
                if(isset($values['from'])) $result = $fld." >= ".$db->quoted($values['from']);
                if(isset($values['to'])) $result = (empty($result)? "" : $result ." AND ") . $fld." <= ".$db->quoted($values['to']);
            }
            $clauses[$field]['checked'] = true;
            if(!empty($result) && !empty($values['or']) && !empty($clauses[$values['or']])){
                $or_resullt = ConsultQFunctions::getClause($values['or'], $clauses[$values['or']], $clauses, $from_new);
            }
        }
        if(!empty($result) && !empty($or_resullt)) $result = "(".$result . (empty($or_resullt) ? "" : " OR ".$or_resullt).")";
        return $result;
    }
    
    public static function getSortList(){
        global $db;
        $sys_tables = Config::$sys_tables;
        $list = $db->fetchall("SELECT sort_num,sort_title FROM ".$sys_tables['consults_sort']." ",'id');
        return $list;
    }
    
    /**
    * проверяем, может ли данный пользователь отвечать на вопросы
    * 
    * @param mixed $id - id пользователя
    */
    public static function checkIfCanAnswer($id_user){
        global $db;
        $sys_tables = Config::$sys_tables;
        if(empty($id_user)) return false;
        $user_info = $db->fetch("SELECT id,user_activity, id_tarif FROM ".$sys_tables['users']." WHERE id = ?",$id_user);
        //если пользователя нет, или это не юрист, или нет нужного тарифа
        return (!(empty($user_info) || $user_info['user_activity'] != 2));
    }
    
    public static function makeSort($sortby){
        global $db;
        $sys_tables = Config::$sys_tables;
        $sortby = Convert::ToInt($sortby);
        $res = false;
        if(!empty($sortby)) $res = $db->fetch("SELECT id FROM ".$sys_tables['consults_sort']." WHERE sort_num = ?",$sortby);
        if(!empty($sortby) && empty($res)) return false;
        switch($sortby){
            // по рейтингу, от большего
            case 1: 
                return $sys_tables['consults'].".rating DESC";
            // по рейтингу, от меньшего
            case 2: 
                return $sys_tables['consults'].".rating ASC";
            // ответов, от большего
            case 3: 
                return $sys_tables['consults'].".answers_amount DESC";
            // ответов, от меньшего
            case 4: 
                return $sys_tables['consults'].".answers_amount ASC";
            //сначала старые
            case 5: 
                return $sys_tables['consults'].".id ASC";
            //по умолчанию - сначала новые
            default:
                return $sys_tables['consults'].".id DESC";                 
        }
    }
    
    public static function getCategoriesList(){
        global $db;
        $sys_tables = Config::$sys_tables;
        $categories = $db->fetchall("SELECT id,title FROM ".$sys_tables['consults_categories']." ORDER BY id",'id');
        return $categories;
    }
}
?>
