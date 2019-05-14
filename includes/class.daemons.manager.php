<?php
    require_once('includes/class.daemon.php');
    
    declare(ticks=1);
    /*
    function sigHandler($signo) {
        global $stop_server;
        switch($signo) {
            case SIGTERM: {
                $stop = true;
                break;
            }
            default: {
            }
        }
    }
    pcntl_signal(SIGTERM, "sigHandler");
    */
    
    
    /**
    * класс для управления действиями из расписания
    */
    class DaemonsManager {
        private $max_childs = 2;
        private $db;
        private $pid;
        private $sys_tables;
        //список дочерних процессов
        private $child_processes;
        //задачи на выполнение
        private $actions_to_do = array();
        
        private function db_connect(){
            $this->db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
            //$this->db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], "h9G9uBN8ubfcTRxd5");
            //$this->db = new mysqli_db(Config::$values['mysql']['host'], "root", "h9G9uBN8ubfcTRxd5");
            $this->db->query("set names ".Config::$values['mysql']['charset']);
        }
        
        public function __construct($db = false){
            $this->child_processes = array();
            //$this->pid = getmypid();
            
            $this->sys_tables = Config::$values['sys_tables'];
            if(empty($db)) $this->db_connect();
            else $this->db = $db;
            
            $this->actions_to_do = array();
            //читаем что сейчас нужно делать
            $scripts_to_do = $this->db->fetchall("SELECT ".$this->sys_tables['daemons_schedule'].".script_alias,
                                                         ".$this->sys_tables['daemons_schedule'].".action_id
                                                  FROM ".$this->sys_tables['daemons_schedule']."
                                                  LEFT JOIN ".$this->sys_tables['daemons']." ON ".$this->sys_tables['daemons_schedule'].".action_id = ".$this->sys_tables['daemons'].".id
                                                  WHERE ".$this->sys_tables['daemons_schedule'].".`action_status` = 1");
                                                        //AND ( action_time > DATE_SUB( NOW( ) , INTERVAL 3 MINUTE ) AND action_time < DATE_ADD( NOW( ) , INTERVAL 3 MINUTE ) ) ");
            //выбираем либо одно действие, либо сразу группу действий(например, daily_stats)
            foreach($scripts_to_do as $key=>$values){
                if(empty($values['script_alias']) && !empty($values['action_id']))
                    $this->actions_to_do[$values['script_alias']] = $this->db->fetchall("SELECT * FROM ".$this->sys_tables['daemons']." WHERE id = ?",false,$values['action_id']);
                elseif(!empty($values['script_alias']))
                    $this->actions_to_do[$values['script_alias']] = $this->db->fetchall("SELECT * FROM ".$this->sys_tables['daemons']."
                                                                                         WHERE script_alias = ? AND status IN(5,6)
                                                                                         ORDER BY number_in_script ASC",false,$values['script_alias']);
            }
            
            $manager_mailer = new EMailer('mail');
            $mail_text = "Демон ежедневной статистики запущен. Задачи для выполнения: \r\n";
            if(!empty($this->actions_to_do['daily_stats']))
                foreach($this->actions_to_do['daily_stats'] as $key=>$item){
                    $mail_text .= "#".$key." ".$item['comment'].": ".Config::$values['daemons_statuses'][$item['status']]."<br />\r\n";
                }
            $html = iconv('UTF-8', $manager_mailer->CharSet, $mail_text);
            // параметры письма
            $manager_mailer->Subject = iconv('UTF-8', $manager_mailer->CharSet, 'Запущен демон ежедневной статистики на bsn.ru');
            $manager_mailer->Body = nl2br($html);
            $manager_mailer->AltBody = nl2br($html);
            $manager_mailer->IsHTML(true);
            $manager_mailer->AddAddress("kya1982@gmail.com");
            $manager_mailer->From = 'daemons_manager@bsn.ru';
            $manager_mailer->FromName = iconv('UTF-8', $manager_mailer->CharSet,' DM BSN.ru');
            // попытка отправить
            $res = $manager_mailer->Send();
            if($res) echo "DM: mail successfully send\r\n";
        }
        
        public function run(){
            
            //пока без разбития на процессы, под win это не работает
            if( function_exists( "pcntl_fork" ) ) {
                $actions_to_check = array();
                while(!empty($this->actions_to_do)){
                    $actions_to_do = array_pop($this->actions_to_do);
                    foreach($actions_to_do as $key=>$action_to_do){
                        $action_to_do['script_path'] = "cron/".$action_to_do['script_alias']."/daemons/".$action_to_do['action_alias'].".php";
                        
                        $pid = pcntl_fork();
                        if ($pid == -1) {
                            echo "cannot create child process";
                        } elseif (empty($pid)) {
                            //для дочернего:
                            $pid = getmypid();
                            echo "child: ".$pid.": process started with task ".$action_to_do['action_alias']."\r\n";
                            $action = new Daemon($action_to_do,false,$pid);
                            $action_res = $action->run();
                            echo "child ".$pid.": finished action ".$action_to_do['action_alias']." with result ".json_encode($action_res)."\r\n";
                            exit();
                        } else {
                            $this->child_processes[$pid] = true;
                            //запоминаем id задачи чтобы потом проверить результат по базе
                            $actions_to_check[] = $action_to_do['id'];
                            //для родительского - продолжаем раздавать задачи, удаляя текущую
                            unset($actions_to_do[$key]);
                            continue;
                        }
                        
                    }
                }
                echo "DM: all started\r\n";
                echo "childs: ".json_encode($this->child_processes)."\r\n";
                $childs_log = array();
                //после того как раздали все задачи, висим, ждем откликов
                while(count($this->child_processes) > 0) {
                    foreach($this->child_processes as $pid => $status) {
                        $res = pcntl_waitpid($pid, $child_status, WNOHANG);
                        
                        // If the process has already exited
                        if($res == -1 || $res > 0){
                            echo "DM: child process ".$pid." ends\r\n";
                            $childs_log[$pid] = $child_status;
                            unset($this->child_processes[$pid]);
                        }
                    }
                    sleep(1);
                }
                echo "DM: all childs finished, executing results:\r\n".json_encode($childs_log)."\r\n";
                //читаем из базы информацию по задачам:
                if(!empty($actions_to_check)){
                    //при необходимости переподключаемся
                    if(!$this->db->ping()) $this->db_connect();
                    $actions_results = $this->db->fetchall("SELECT id,comment,status FROM ".$this->sys_tables['daemons']." WHERE id IN(".implode(',',$actions_to_check).")");
                    
                    $manager_mailer = new EMailer('mail');
                    $mail_text = "Отчет по выполнению ежедневной статистики: \r\n";
                    foreach($actions_results as $key=>$item) $mail_text .= "#".$key." ".$item['comment'].": ".Config::$values['daemons_statuses'][$item['status']]."<br />\r\n";
                    
                    $full_log = ob_clean();
                    if(!empty($full_log)) $mail_text .= "<br />Лог: ".$full_log;
                    
                    $html = iconv('UTF-8', $manager_mailer->CharSet, $mail_text);
                    // параметры письма
                    $manager_mailer->Subject = iconv('UTF-8', $manager_mailer->CharSet, 'Ежедневная статистика на bsn.ru');
                    $manager_mailer->Body = nl2br($html);
                    $manager_mailer->AltBody = nl2br($html);
                    $manager_mailer->IsHTML(true);
                    $manager_mailer->AddAddress("kya1982@gmail.com");
                    $manager_mailer->AddAddress("hitty@bsn.ru");
                    $manager_mailer->From = 'daemons_manager@bsn.ru';
                    $manager_mailer->FromName = iconv('UTF-8', $manager_mailer->CharSet,' DM BSN.ru');
                    // попытка отправить
                    $res = $manager_mailer->Send();
                    if($res) echo "DM: mail successfully send\r\n";
                }
            }else{
                while(!empty($this->actions_to_do)){
                    $actions_to_do = array_pop($this->actions_to_do);
                    foreach($actions_to_do as $key=>$action_to_do){
                        
                        $action_to_do['script_path'] = "cron/".$action_to_do['script_alias']."/daemons/".$action_to_do['action_alias'].".php";
                        $action = new Daemon($action_to_do,$this->db);
                        $action_res = $action->run();
                        
                        unset($action);
                    }
                }
            }
        }
        
        private function checkActionsDone($ids){
            $result = $this->db->fetch("SELECT SUM(status IN(2,4)) AS finished_sum, COUNT(*) AS amount FROM ".$this->sys_tables['daemons']." WHERE id IN(".$ids.")");
            return (!empty($result) && $result['finished_sum'] == $result['amount']);
        }
        
        public function getActualAction(){
            $action_to_do = $this->db->fetch("SELECT ".$this->sys_tables['daemons'].".*
                                                     FROM ".$this->sys_tables['daemons_schedule']."
                                                     LEFT JOIN ".$this->sys_tables['daemons']." ON ".$this->sys_tables['daemons_schedule'].".action_id = ".$this->sys_tables['daemons'].".id
                                                     WHERE ".$this->sys_tables['daemons_schedule'].".`action_status` = 1 AND
                                                           ".$this->sys_tables['daemons']." OR 
                                                           ( action_time > DATE_SUB( NOW( ) , INTERVAL 3 MINUTE ) AND action_time < DATE_ADD( NOW( ) , INTERVAL 3 MINUTE ) ) )");
            return $action_to_do;
        }
        
        public function getFailedActions(){
            $failed_actions = $this->db->fetchall("SELECT *
                                                   FROM ".$this->sys_tables['daemons_schedule']."
                                                   WHERE ".$this->sys_tables['daemons_schedule'].".`action_status` = 3");
            return $failed_actions;
        }
        
        public function resetDaemons(){
            $res['result'] = $this->db->query("UPDATE ".$this->sys_tables['daemons']." SET status = 6 WHERE status IN (1,2)");
            $res['errors'] = $this->db->fetch("SELECT GROUP_CONCAT(action_alias) AS action_failed FROM ".$this->sys_tables['daemons']." WHERE status != 6");
            if(!empty($res['errors']) && !empty($res['errors']['action_failed'])) $res['errors'] = $res['errors']['action_failed'];
            else $res['errors'] = false;
            $res['affected_rows'] = $this->db->affected_rows;
            return $res;
        }
        
        public function updateDaemons(){
            $res['result'] = $this->db->query("UPDATE ".$this->sys_tables['daemons']." SET status = 6 WHERE status != 2");
            $res['errors'] = $this->db->fetch("SELECT GROUP_CONCAT(action_alias) AS action_failed FROM ".$this->sys_tables['daemons']." WHERE status != 6");
            if(!empty($res['errors']) && !empty($res['errors']['action_failed'])) $res['errors'] = $res['errors']['action_failed'];
            else $res['errors'] = false;
            $res['affected_rows'] = $this->db->affected_rows;
            return $res;
        }
        
    }
?>
