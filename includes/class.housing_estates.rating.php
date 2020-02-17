<?php
include_once('includes/phpmailer/class.phpmailer.php');
if( !class_exists('HousingEstates') ) include('includes/housing_estates.php');
class HousingEstatesRating {
    private $tables = [];
    public $housing_estates_ids = [];
    public function __construct(){
        $this->tables = Config::$sys_tables;    
    }
    
    
    public function sentInvite( $id_expert ){
        global $db;
        $user_info = $this->userInfo( $id_expert );
        Response::SetArray( 'user_info' , $user_info );
        //отправка письма
        $mailer = new EMailer('mail'); 
        $eml_tpl = new Template('expert.invite.email.html', '/modules/housing_estates_rating/');
        $html = $eml_tpl->Processing();
        echo $html = iconv('UTF-8', $mailer->CharSet.'//IGNORE', $html);
        $mailer->Body = $html;
        $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Голосование за ЖК');
        $mailer->IsHTML(true);
        $mailer->AddAddress( $user_info['email'] );
        $mailer->From = 'pr@bsn.ru';
        $mailer->FromName = iconv('UTF-8', $mailer->CharSet, "BSN.ru");
        // попытка отправить
        $db->query(" UPDATE ".$this->tables['housing_estates_experts']." SET sent_mail = 3 WHERE id = ?", $id_expert);
        return $mailer->Send();
        
    }
    
    public function userInfo( $id_expert = false,  $id_user = false, $token = false ){
        global $db;
        $where = [];
        if( !empty( $id_expert ) ) $where[] = $this->tables['housing_estates_experts'].".id = " . $id_expert;
        if( !empty( $token ) ) $where[] = $this->tables['housing_estates_experts'].".token = '" . $db->real_escape_string( $token) . "'";
        else if( !empty( $id_user ) ) $where[] = $this->tables['housing_estates_experts'].".id_user = " . $id_user;
        
        if( empty( $where ) ) return false;
        $where = " WHERE " . implode( " AND ", $where );
        $item = $db->fetch("SELECT 
                                GROUP_CONCAT(dd.title) as district_title,
                                GROUP_CONCAT(dd.title_genitive) as district_title_genitive,
                                GROUP_CONCAT(dd.housing_estates_ids) as housing_estates_ids,
                                ".$this->tables['housing_estates_experts'].".*,
                                IF(
                                    YEAR(" . $this->tables['housing_estates_experts'] . ".`date`) < Year(CURDATE()),
                                        DATE_FORMAT(" . $this->tables['housing_estates_experts'] . ".`date`, '%e %M %Y'),
                                        DATE_FORMAT(" . $this->tables['housing_estates_experts'] . ".`date`, '%e %M')
                                ) as normal_date, 
                                ".$this->tables['users'].".name,
                                ".$this->tables['users'].".lastname,
                                ".$this->tables['agencies'].".title as agency_title,
                                ".$this->tables['housing_estates_experts_photos'].".name as photo_name,
                                LEFT(".$this->tables['housing_estates_experts_photos'].".name,2) as photo_subfolder

                            FROM ".$this->tables['housing_estates_experts']." 
                            RIGHT JOIN ".$this->tables['users']." ON ".$this->tables['housing_estates_experts'].".id_user = ".$this->tables['users'].".id
                            LEFT JOIN ".$this->tables['agencies']." ON ".$this->tables['agencies'].".id = ".$this->tables['users'].".id_agency
                            LEFT JOIN ".$this->tables['housing_estates_experts_photos']." ON ".$this->tables['housing_estates_experts_photos'].".id = ".$this->tables['housing_estates_experts'].".id_main_photo
                            LEFT JOIN (
                                SELECT 
                                    id,
                                    GROUP_CONCAT( ' ', d.title) as title,
                                    GROUP_CONCAT( ' ', d.title_genitive) as title_genitive,
                                    GROUP_CONCAT(d.housing_estates_ids) as housing_estates_ids
                                FROM ".$this->tables['housing_estates_districts']." d 
                                GROUP BY d.id
                            ) dd ON FIND_IN_SET( dd.id, ".$this->tables['housing_estates_experts'].".districts ) 
                            " . ( !empty( $where) ? $where : "" ) . " 
                            GROUP BY ".$this->tables['housing_estates_experts'].".id
                            "
                            
        ) ;
        $this->housing_estates_ids = !empty( $item['housing_estates_ids'] ) ? explode( ",", $item['housing_estates_ids'] ) : [];
        return $item;
        
    }
    
    public function canVote( $id_housing_estate ){
        return in_array( $id_housing_estate, $this->housing_estates_ids ) && empty( $this->itemRating( $id_housing_estate ) );
    }

    public function itemRating( $id_housing_estate ){
        global $auth, $db;
        $item = $db->fetch(" SELECT * FROM " . $this->tables['housing_estates_voting'] ." WHERE id_user = ? AND id_parent = ?",
                            $auth->id, $id_housing_estate
        );
        return $item;
    }
    
    public function voteComplete(){
        global $auth, $db;
        $housing_estates = new HousingEstates();
        $list = $housing_estates->Search( $this->tables['housing_estates'] . ".id IN (" . implode( ",", $this->housing_estates_ids ) . ")", 1000, 0, false, $auth->id );
        foreach( $list as $k => $item) if( !empty( $this->canVote( $item['id'] ) ) ) return false;
        return true;
    }
    
    /**
    * получение рейтинга
    * @param integer $id_district
    * @return array 
    */                    
    public function getRatingList($id_district = false, $expert = false, $count=false, $order = false, $id_area = false, $where = false){
        global $db;      
        $conditions = array( $this->tables['housing_estates'].".published = 1" );
        if(!empty($id_district)) $conditions[] = $this->tables['housing_estates'].".id_district IN (".$id_district.")";
        if(!empty($id_area)) $conditions[] = $this->tables['housing_estates'].".id_area IN (".$id_area.")";
        if(!empty($expert)) $conditions[] = $this->tables['housing_estates_voting'].".is_expert = " . ( $expert === 2 ? 2 : 1 );
        
        $where = ( !empty( $where ) ? $where . " AND " : "" ) . implode(" AND ", $conditions);
        $list = $db->fetchall("
            SELECT 
                ".$this->tables['housing_estates'].".id,
                ".$this->tables['housing_estates'].".title,
                ".$this->tables['housing_estates'].".chpu_title,
                ".$this->tables['housing_estates'].".class,
                 IF(".$this->tables['housing_estates'].".class = 3, 'Премиум',
                    IF(".$this->tables['housing_estates'].".class = 2, 'Бизнес',
                    IF(".$this->tables['housing_estates'].".class = 4, 'Комфорт','Эконом'))
                 ) as class_title,
                ROUND( AVG(".$this->tables['housing_estates_voting'].".rating) , 2) as rating,
                ROUND( AVG(".$this->tables['housing_estates_voting'].".rating_transport) , 2) as rating_transport,
                ROUND( AVG(".$this->tables['housing_estates_voting'].".rating_infrastructure) , 2) as rating_infrastructure,
                ROUND( AVG(".$this->tables['housing_estates_voting'].".rating_safety) , 2) as rating_safety,
                ROUND( AVG(".$this->tables['housing_estates_voting'].".rating_ecology) , 2) as rating_ecology,
                ROUND( AVG(".$this->tables['housing_estates_voting'].".rating_quality) , 2) as rating_quality,
                COUNT(".$this->tables['housing_estates_voting'].".id) as voters
            FROM ".$this->tables['housing_estates']."
            LEFT JOIN ".$this->tables['housing_estates_voting']." ON ".$this->tables['housing_estates_voting'].".id_parent = ".$this->tables['housing_estates'].".id
            WHERE ".$where."
            GROUP BY ".$this->tables['housing_estates'].".id 
            HAVING COUNT(".$this->tables['housing_estates_voting'].".id) > 2
            ".(!empty($expert) ? " ORDER BY rating DESC " : "")."
            ".(!empty($count) ? " LIMIT ".$count : "")."
        ");
        
        $rating_fields = [];
        foreach($list as $k=>$item) {
            $housing_estate = new HousingEstates();
            $list[$k]['item'] = $housing_estate->getItem($item['id']);
            $list[$k]['photo'] = Photos::getMainPhoto('housing_estates', $item['id']);
            $list[$k]['objects'] = $housing_estate->getObjectsList($item['id']);
            $queries = $housing_estate->getQueries($item['id']);
            $years = [];
            foreach($queries as $q=>$query) {
                if( $query['year'] == 3 || $query['year'] >= date('Y')) $years[] = $query['year'] == '3' ? 'Сдан' : $query['year'];
            }
            $years = array_unique($years);
            foreach($years as $y=>$year) {
                if($year == 'Сдан' && count($years) > 1) unset($years[$y]);
            }
            $list[$k]['years'] = implode(", ", $years);
        }
        

        return $list;
    }    
}

?>