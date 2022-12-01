<?php
    class Daemon{
        private $pid;
        private $db;
        private $sys_tables;
        private $action_id;
        private $action_info;
        private $sleeping_limit = 720;
        private $email_for_help = "web@bsn.ru";
        
        public function __construct($action_info,$db=false,$pid = false){
            global $db;
            if(empty($action_info['id'])) return false;
            $this->action_id = $action_info['id'];
            $this->action_info = $action_info;
            
            $this->sys_tables = Config::$values['sys_tables'];
            $this->pid = $pid;
            if( empty( $db ) ) {
                $this->db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
                $this->db->querys("set names ".Config::$values['mysql']['charset']);
            } 
            else $this->db = $db;
        }
        
        private function writeStats($run_response){
            return $this->db->insertFromArray($this->sys_tables['daemons_stats'],$run_response);
        }
        
        public function activate(){
            $this->setStatus(1);
        }
        public function finish(){
            $this->setStatus(2);
        }
        public function error($status=false){
            $this->setStatus((!empty($status) ? $status : 3));
        }
        
        public function getActionInfo($fieldname){
            return $this->action_info[$fieldname];
        }
        
        private function setStatus($status){
            return $this->db->querys("UPDATE ".$this->sys_tables['daemons']." SET status = ? WHERE id = ?",$status,$this->action_info['id']);
        }
        
        public function getStatus($res=false){
            if(empty($res)) return 3;
            else return 2;
        }
        
        private function getRunConditionsScripts(){
            $result = $this->db->fetchall("SELECT CONCAT(script_alias,'/',action_alias) AS script_name, status
                                           FROM ".$this->sys_tables['daemons']."
                                           WHERE id IN(".$this->action_info['required_actions'].") AND status != 2");
            return $result;
        }
        
        //проверяем условия выполнения
        private function checkRunConditions(){
            if(empty($this->action_info['required_actions'])) return true;
            $result = $this->db->fetch("SELECT SUM(status IN(2,4)) AS finished_sum, COUNT(*) AS amount 
                                        FROM ".$this->sys_tables['daemons']." 
                                        WHERE id IN(".$this->action_info['required_actions'].")");
            return (!empty($result) && $result['finished_sum'] == $result['amount']);
        }
        
        private function sendCallHelpMail( $email =  '' ){
            $mailer = new EMailer('mail');
            $mail_text = "Скрипт долго ждет слишком долго:\r\n";
            
            $mail_text = $this->action_info['script_path']." ждет скриптов: \r\n";
            
            $waiting_for = $this->getRunConditionsScripts();
            
            foreach($waiting_for as $key=>$item){
                $mail_text .= $item['script_name'].", ".$status."\r\n";
            }
            
            $html = iconv('UTF-8', $mailer->CharSet, $mail_text);
            // параметры письма
            $mailer->Subject = iconv('UTF-8', $mailer->CharSet, 'Ожидание скрипта статистики');
            $mailer->Body = nl2br($html);
            $mailer->AltBody = nl2br($html);
            $mailer->IsHTML(true);
            $mailer->AddAddress($this->email_for_help);
            $mailer->From = 'xml_parser@bsn.ru';
            $mailer->FromName = iconv('UTF-8', $mailer->CharSet,' DM BSN.ru');
            // попытка отправить
            $res = $mailer->Send();
            if($res) echo "child: mail for help successfully send\r\n";
            return $res;
        }
        
        public function run(){
            //отмечаем что задача взята в работу
            echo $this->pid." started\r\n";
            $this->activate();
            $sleeping_time = 0;
            $mail_for_help = false;
            if( function_exists( "pcntl_fork" )  ) {
                //если условий еще нет, ждем
                while(!$this->checkRunConditions() && $sleeping_time < $this->sleeping_limit){
                    sleep(5);
                    echo $this->pid." is waiting for others\r\n";
                    $sleeping_time += 5;
                    if($sleeping_time * 3 > $this->sleeping_limit && !$mail_for_help){
                        $this->sendCallHelpMail();
                        $mail_for_help = true;
                    } 
                }
            }
            //если так и не дождались, завершаемся с ошибкой
            if( !$this->checkRunConditions() ) {
                $this->error(7);
                return 7;
            }
            //если все в порядке, выполняем     
            $script_path = $this->action_info['script_path'];
            if(!empty($script_path)) $run_response = require_once( $script_path );
            echo "child ".$this->pid." finished with run_response = ".$run_response['result_status'].";\r\n";
            $this->setStatus($run_response['result_status']);
            $this->writeStats($run_response);
            $response = (empty($run_response['result_status']) ? $this->getStatus() : $run_response['result_status']);
            $this->db->close();
            return $response;
        }
    }
?>
