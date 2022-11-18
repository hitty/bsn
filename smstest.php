<?php
include('includes/class.config.php');       // Config (конфигурация сайта)
Config::Init();
include('includes/class.convert.php');      // Convert, Validate (конвертирование, проверки валидности)
include('includes/class.db.mysqli.php');    // mysqli_db (база данных)
$db = new mysqli_db(Config::$values['mysql']['host'], Config::$values['mysql']['user'], Config::$values['mysql']['pass']);
$db->querys("set names ".Config::$values['mysql']['charset']);
include('includes/class.sms.php');

$smsObj = new SMSSender();
$smsObj->smsSend("Тестовое\rсообщение\nБСН", array('79219524290'),15);
//$result = $smsObj->smsCheckStatus(array(8,9));

/*

--
-- Структура таблицы `sms_log`
--

CREATE TABLE IF NOT EXISTS `sms_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_text` int(10) unsigned NOT NULL,
  `push_id` varchar(255) NOT NULL DEFAULT '',
  `number` varchar(25) NOT NULL DEFAULT '',
  `ttl` int(5) unsigned NOT NULL DEFAULT '0',
  `code` int(7) NOT NULL DEFAULT '-9999',
  `status` varchar(255) NOT NULL DEFAULT '',
  `sms_count` int(1) unsigned NOT NULL DEFAULT '1',
  `delivery_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `id_text` (`id_text`),
  KEY `push_id` (`push_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

--
-- Дамп данных таблицы `sms_log`
--

INSERT INTO `sms_log` (`id`, `id_text`, `push_id`, `number`, `ttl`, `code`, `status`, `sms_count`, `delivery_datetime`) VALUES
(8, 9, '24588000000000003', '79062488292', 15, 4, 'SMS доставлена', 1, '2013-01-11 13:35:00'),
(9, 9, '24588000000000004', '79117822233', 15, 4, 'SMS доставлена', 1, '2013-01-11 13:35:00');

-- --------------------------------------------------------

--
-- Структура таблицы `sms_text`
--

CREATE TABLE IF NOT EXISTS `sms_text` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `text` varchar(255) NOT NULL DEFAULT '',
  `from` varchar(20) NOT NULL DEFAULT '',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  `success` int(10) unsigned NOT NULL DEFAULT '0',
  `create_date` datetime NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

--
-- Дамп данных таблицы `sms_text`
--

INSERT INTO `sms_text` (`id`, `text`, `from`, `count`, `success`, `create_date`, `status`) VALUES
(8, 'Тестовое сообщение', 'test-sms', 1, 1, '2013-01-11 12:44:03', 'OK'),
(9, 'Тестовое сообщение', 'test-sms', 2, 2, '2013-01-11 13:34:01', 'OK');
 
*/
?>
