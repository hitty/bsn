<?php
/**
 * Класс REST API Sendpulse
 */
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Sendpulse
{
    public function __construct()
    {
    }

    public function sendMail(
        $subject,
        $body,
        $reciever_name = '',
        $reciever_email = '',
        $emails = '',
        $attachments = ''
    )
    {
        if (empty($subject) || empty($body) || (empty($reciever_email) && empty($emails))) return $this->handleError('Empty email data');

        foreach ($emails as $email) {
            $mail = new PHPMailer(true);
            try {
                // Настройки SMTP
                $mail->isSMTP();
                $mail->Host = 'smtp.yandex.ru'; // SMTP-сервер yandex.ru
                $mail->SMTPAuth = true;
                $mail->Username = 'no-reply@bsn.ru'; // Ваш email
                $mail->Password = 'eterrblvvncziziu';      // Ваш пароль
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
                $mail->Port = 465; // Порт для SSL
                $mail->CharSet = 'UTF-8';
                $mail->isHTML(true);

                // Отправитель и получатель
                $mail->setFrom('no-reply@bsn.ru', $reciever_name);
                $mail->addAddress($email['email'], $email['name']);

                // Содержимое письма
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $body;
                $mail->AltBody = '';

                $mail->send();
                echo 'Письмо успешно отправлено!';
            } catch (Exception $e) {
                echo "Ошибка при отправке письма: {$mail->ErrorInfo}";
            }
        }

    }

}

?>