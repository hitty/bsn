<?php
if( !class_exists('Sendpulse') ) require_once("includes/class.sendpulse.php");

$sendpulse = new Sendpulse( );

/*
 * Example: Get Mailing Lists
 */
var_dump( $sendpulse->listAddressBooks() );

/*
 * Example: Add new email to mailing lists
 */

$emails = array(
    array(
        'email' => 'hitty@bsn.ru',
        'variables' => array(
            'phone' => '+79117822233',
            'name' => 'Юрий Кружевицких',
        )
    )
);
var_dump( $sendpulse->addEmails( false, $emails ) );

die();

/*
 * Example: Send mail using SMTP
 */
$email = array(
    'html' => '<p>Hello!</p>',
    'text' => 'Hello!',
    'subject' => 'Mail subject',
    'from' => array(
        'name' => 'John',
        'email' => 'sender@example.com',
    ),
    'to' => array(
        array(
            'name' => 'Subscriber Name',
            'email' => 'subscriber@example.com',
        ),
    ),
    'bcc' => array(
        array(
            'name' => 'Manager',
            'email' => 'manager@example.com',
        ),
    ),
    'attachments' => array(
        'file.txt' => file_get_contents(path_to_attached_files),
    ),
);
var_dump($SPApiClient->smtpSendMail($email));

/*
 * Example: create new push
 */
$task = array(
    'title' => 'Hello!',
    'body' => 'This is my first push message',
    'website_id' => 1,
    'ttl' => 20,
    'stretch_time' => 0,
);

// This is optional
$additionalParams = array(
    'link' => 'http://yoursite.com',
    'filter_browsers' => 'Chrome,Safari',
    'filter_lang' => 'en',
    'filter' => '{"variable_name":"some","operator":"or","conditions":[{"condition":"likewith","value":"a"},{"condition":"notequal","value":"b"}]}',
);
var_dump($SPApiClient->createPushTask($task, $additionalParams));
?>