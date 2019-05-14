<?php
require_once('includes/phpmailer/class.phpmailer.php');

/**
* Расширение класса PhpMailer для возможности переопределения настроек без переписывания основного класса
*/
class EMailer extends phpmailer {
    public $Mailer = "mail";
    public $CharSet = 'windows-1251';
    
    /**
    * отправка письма пользователю
    * 
    * @param mixed $email - email пользователя
    * @param mixed $name - имя пользователя
    * @param mixed $subject - тема письма
    * @param mixed $template_name - название шаблона
    * @param mixed $template_path - путь к шаблону
    * @param mixed $letter_data - данные для Response в письмо
    * @param mixed $mail_text - заданный текст письма. альтернатива шаблону
    * @param mixed $from - от кого (почта)
    * @return bool
    */
    public function sendEmail($email, $username, $subject, $template_name, $template_path, $letter_data = false, $mail_text = false, $from_email = false, $separate_send = false){
        
        if(!is_array($email)) $email = array($email);
        if(!is_array($username)) $username = array($username);
        
        $email = array_filter($email,function($v){return Validate::isEmail($v);});
        
        if(empty($email) || (empty($template_name) && empty($template_path) && empty($mail_text))) return false;
        
        if(!empty($letter_data))
            foreach($letter_data as $name=>$value){
                switch(true){
                    case is_array($value): 
                        Response::SetArray($name,$value);
                        break;
                    case is_bool($value): 
                        Response::SetBoolean($name,$value);
                        break;
                    default:
                        Response::SetString($name,$value);
                }
            }
        if(empty($template_name) && empty($template_path)) $html = $mail_text;
        else{
            $eml_tpl = new Template($template_name, $template_path);
            $html = $eml_tpl->Processing();
        } 
        $html = iconv('UTF-8', $this->CharSet, $html);
        if(!empty($separate_send)){
            $result = true;
            foreach($email as $key=>$email_address){
                $this->ClearAddresses();
                $this->Body = $html;
                $this->AltBody = strip_tags($html);
                $this->Subject = iconv('UTF-8', $this->CharSet, $subject);
                $this->IsHTML(true);
                if(isset($username[$key])) $this->AddAddress($email_address, iconv('UTF-8',$this->CharSet, $username[$key]));
                else continue;
                $this->From = (!empty($from_email) ? $from_email : 'no-reply@bsn.ru');
                $this->FromName = 'bsn.ru';
                $result *= $this->Send();
            }
        }else{
            $this->Body = $html;
            $this->AltBody = strip_tags($html);
            $this->Subject = iconv('UTF-8', $this->CharSet, $subject);
            $this->IsHTML(true);
            foreach($email as $key=>$email_address){
                if(isset($username[$key])) $this->AddAddress($email_address, iconv('UTF-8',$this->CharSet, $username[$key]));
            }
            
            $this->From = (!empty($from_email) ? $from_email : 'no-reply@bsn.ru');
            $this->FromName = 'bsn.ru';
            return $this->Send();
        }
        
    }
}
?>