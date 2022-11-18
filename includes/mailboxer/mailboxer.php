#!/usr/local/bin/php
<?php
error_reporting(E_ALL);

// managing execution properties {{{
	$DELETE = false;
	$QUIET = false;

	function yesno($val)
	{
		if($val == 'yes')
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	foreach($argv as $arg)
	{
		preg_match("/^--(.*)(?:=(.*))?$/U",$arg,$matches);
		@list(,$title,$value) = $matches;

		switch(strtolower($title))
		{
			case 'delete':
				$DELETE = yesno($value);
				break;
			case 'quiet':
				$QUIET = yesno($value);
				break;
			case 'help':
				echo "\n";
				echo "  USAGE: \n";
				echo "    ./mailboxer.php [options]\n";
				echo "\n";
				echo "  AVAILABLE OPTIONS:\n";
				echo "    --delete (delete messages from server?, default: \"no\")\n";
				echo "    --quiet (dont output anything to stdout?, default: \"yes\")\n";
				echo "    --help (this screen)\n";
				echo "\n";
				echo "  NOTICE:\n";
				echo "    Each option (except \"--help\" =)) has two values (yes|no)\n";
				echo "\n";
				die();
				break;
		}
	} //}}}


// loading configuration
	$config = parse_ini_file('/home/bsnrobot/configuration.ini');


//BLOCK: requiries {{{
	// require database class
		require('/home/apache/htdocs/includes/db/mysql.core.php');
		$db->querys('set names koi8r');

	// require mailer class
		require('/home/dither/lib/mailer.class.php');
		$mailer = new mailer;

	// require mailboxer class
		require('/home/bsnrobot/mailboxer.class');

	// require errors trigs
		require('/home/bsnrobot/user_messages.class.php');

	// require utf convert function
		require('/home/bsnrobot/function_utf.php');

	// require dlog class
		require('/home/bsnrobot/dlog.class');

//BLOCKEND}}}

// CREATING MAILBOXER INSTANCE
	//$mailboxer = new mailboxer('81.222.134.6:110','bsnrobot','somepass');
	$mailboxer = new mailboxer('pop3.sweb.ru:110','bsn.ru+bsnrobot','ngzOzX._91s');


function checkLogin($login, $passwd) // {{{
{
	global $db;

	$sql = "SELECT
				`users`.`id`
			FROM
				`auth`.`users`
			WHERE
				`login` = '" . $login . "' AND
				`passwd` = '" . $passwd . "'
			LIMIT 1;";

	$result = $db->querys($sql);

	if($db->affected_rows == 1)
	{
		list($id) = $result->fetch_array(MYSQL_NUM);
		return $id;
	}
	else
	{
		return false;
	}
} //}}}

function send_answer()
{
	global $user_errors;
	foreach($user_errors as $error)
	{
		echo '  > ' . getErrDescription($error) . "\n";
	}
}


// gets next available filename in target directory
function getNextName($directory, $prefix, $extension, $digits) // {{{
{
	$contents = scandir($directory);
	unset($contents[0],$contents[1]);

	$indexes = array(0);

	foreach($contents as $item)
	{
		if(preg_match("/^$prefix([0-9]\{$digits})\.$extension$/",$item,$matches))
		{
			array_push($indexes, (int) $matches[1]);
		}
		else
		{
			continue;
		}
	}

	$next_index = str_pad(max($indexes) + 1, $digits, 0, STR_PAD_LEFT);
	$next_filename = $directory . '/' . $prefix . $next_index . '.' . $extension;

	return $next_filename;
} // }}}

// converts pseudo-eng symbols to eng
function rueng2eng($str) // {{{
{
	$symb = array
				(
					array('�','a'),
					array('�','A'),
					array('�','B'),
					array('�','e'),
					array('�','E'),
					array('�','3'),
					array('�','M'),
					array('�','H'),
					array('�','o'),
					array('�','O'),
					array('�','p'),
					array('�','P'),
					array('�','c'),
					array('�','C'),
					array('�','T'),
					array('�','y'),
					array('�','Y'),
					array('�','x'),
					array('�','X')
				);

	foreach($symb as $item)
	{
		$sRu[] = $item[0];
		$sEn[] = $item[1];
	}

	return str_replace($sRu,$sEn,$str);
} // }}}

if($mailboxer->msg_count() > 0)
{
	$dlog = new dlog('koi8-r','koi8-r');
	$dlog_messwalk = $dlog->addGroup('START_WALKING_ON_MESSAGES');

	for($current_msgno = 1; $current_msgno <= $mailboxer->msg_count(); $current_msgno++)
	{
		$user_messages = new user_messages_sender;

		$dlog_currmess = $dlog_messwalk->addGroup('FETHCING_ONE_MESSAGE');
		$dlog_currmess->addItem('CURRENT_MESSAGE_NUMBER', dlog::ITEM_TYPE_INFO, $current_msgno);

		// fetching message
			$dlog_currmess->addItem('FETCH_MESSAGE_CONTENT', dlog::ITEM_TYPE_INFO);

			$msg_info = $mailboxer->fetch_message($current_msgno);

		// params used by user_message_sender class

			$queue_params = new stdClass;
			$queue_params->type = 'mail';
			$queue_params->from_addr = $msg_info->from_addr;
			$queue_params->from_name = $msg_info->from_name;
			$queue_params->subject = $msg_info->subject;
			$queue_params->current_stage = 'mail_parsing';
			$queue_params->date_fetched = date('Y-m-d H:i:s');

			$dlog_currmess->addItem('FOUNDED_SENDER',dlog::ITEM_TYPE_INFO,$msg_info->sender_addr);
			if(!empty($msg_info->subject)) $dlog_currmess->addItem('FOUNDED_SUBJECT', dlog::ITEM_TYPE_INFO);

		// defaults
			$login = false;
			$passwd = false;
			$format = false;
			$ofrom = false;
			$authorized = false;
			$ofrom_default = 'es';


		// starting walking on message parts (searching login, passwd, ofrom and format)
			$dlog_currmess->addItem('TOTAL_PARTS',dlog::ITEM_TYPE_INFO, sizeof($msg_info->parts));
			$dlog_walkparts = $dlog_currmess->addGroup('WALKING_ON_PARTS_SEARCH_PROPERTIES');
			foreach($msg_info->parts as $part_num => $part)
			{
				$dlog_walkpart = $dlog_walkparts->addGroup('WALKING_ON_PART_'.$part_num);

				//BLOCK: SEARCHING PLAIN PART, EXTRACTING LOGIN, PASSWD AND OTHER FIELDS{{{
					$dlog_walkpart->addItem('DETERMINING_PART_TYPE',dlog::ITEM_TYPE_INFO);
					if($part->type === 0)
					{
						$dlog_walkpart->addItem('PART_TYPE_SUBTYPE', dlog::ITEM_TYPE_INFO, $part->type, $part->subtype);
						if($part->subtype == 'HTML')
						{
							$part->content = preg_replace("/\<br(\/)?\>/i","\n",$part->content);
							$part->content = str_replace("&nbsp;"," ",$part->content);
							$dlog_walkpart->addItem('STRIP_HTML_TAGS', dlog::ITEM_TYPE_INFO);
							$part->content = strip_tags($part->content);
						}


						$dlog_searchlogin = $dlog_walkpart->addGroup('SEARCHING_LOGIN');
						if(preg_match("/login\:[\ ]*([^\ \r\n]+)/i",$part->content, $matches))
						{
							$dlog_searchlogin->addItem('LOGIN_FOUNDED',dlog::ITEM_TYPE_INFO, $matches[1]);

							// trimming white space
							$login = trim($matches[1]);
							if($login != $matches[1]) $dlog_searchlogin->addItem('LOGIN_SPACE_TRIMMED',dlog::ITEM_TYPE_INFO, $matches[1], $login);

							// replacing peng to eng
							$tmp = $login;
							$login = rueng2eng($tmp);
							if($login != $tmp) $dlog_searchlogin->addItem('LOGIN_PENG_CONVERTED',dlog::ITEM_TYPE_INFO,$tmp,$login);

						}
						else
						{
							$dlog_searchlogin->addItem('LOGIN_NOT_FOUND',dlog::ITEM_TYPE_WARNING);
						}


						$dlog_searchpasswd = $dlog_walkpart->addGroup('SEARCHING_PASSWD');
						if(preg_match("/password\:[\ ]*([^\ \r\n]+)/i",$part->content, $matches))
						{
							$dlog_searchpasswd->addItem('PASSWD_FOUNDED',dlog::ITEM_TYPE_INFO, $matches[1]);

							// trimming white space
							$passwd = trim($matches[1]);
							if($passwd != $matches[1]) $dlog_searchpasswd->addItem('PASSWD_SPACE_TRIMMED',dlog::ITEM_TYPE_INFO, $matches[1], $passwd);

							// replacing peng to eng
							$tmp = $passwd;
							$passwd = rueng2eng($tmp);
							if($passwd != $tmp) $dlog_searchpasswd->addItem('PASSWD_PENG_CONVERTED',dlog::ITEM_TYPE_INFO,$tmp,$passwd);
						}
						else
						{
							$dlog_searchpasswd->addItem('PASSWD_NOT_FOUND',dlog::ITEM_TYPE_WARNING);
						}


						$dlog_searchformat = $dlog_walkpart->addGroup('SEARCHING_FORMAT');
						if(preg_match("/format\:[\ ]*([^\ \r\n]+)/i",$part->content, $matches))
						{
							$dlog_searchformat->addItem('FORMAT_FOUNDED',dlog::ITEM_TYPE_INFO, $matches[1]);

							// trimming white space
							$format = strtolower(trim($matches[1]));
							if($format != $matches[1]) $dlog_searchformat->addItem('FORMAT_SPACE_TRIMMED_LOWERCASE',dlog::ITEM_TYPE_INFO, $matches[1], $format);

							// replacing peng to eng
							$tmp = $format;
							$format = rueng2eng($tmp);
							if($format != $tmp) $dlog_searchformat->addItem('FORMAT_PENG_CONVERTED',dlog::ITEM_TYPE_INFO,$tmp,$format);
						}
						else
						{
							$dlog_searchformat->addItem('FORMAT_NOT_FOUND',dlog::ITEM_TYPE_WARNING);
						}


						$dlog_searchofrom = $dlog_walkpart->addGroup('SEARCHING_OFROM');
						if(preg_match("/ofrom\:[\ ]*([^\ \r\n]+)/i",$part->content, $matches))
						{
							$dlog_searchofrom->addItem('OFROM_FOUNDED',dlog::ITEM_TYPE_INFO, $matches[1]);

							// trimming white space
							$ofrom = strtolower(trim($matches[1]));
							if($ofrom != $matches[1]) $dlog_searchofrom->addItem('OFROM_SPACE_TRIMMED_LOWERCASE',dlog::ITEM_TYPE_INFO,$matches[1],$ofrom);

							$tmp = $ofrom;
							$ofrom = rueng2eng($tmp);
							if($ofrom != $tmp) $dlog_searchofrom->addItem('OFROM_PENG_CONVERTED',dlog::ITEM_TYPE_INFO,$tmp,$ofrom);
						}
						else
						{
							$dlog_searchofrom->addItem('OFROM_NOT_FOUND',dlog::ITEM_TYPE_WARNING);
						}

						// deleting text part after searching login
					}
					else
					{
						$dlog_walkpart->addItem('NO_PLAIN_TEXT_PART',dlog::ITEM_TYPE_WARNING,$part->type);
					}
				//BLOCKEND}}}
			}

			//BLOCK: TESTING LOGIN, PASSWD, OFROM, FORMAT AND DELETE FIELDS{{{
				// login
					if($login === false)
					{
						$dlog_currmess->addItem('LOGIN_NOT_FOUND',dlog::ITEM_TYPE_ERROR);
						$user_messages->add('NO_LOGIN','ERROR');
						$next_filename = $mailboxer->save_file($current_msgno, $config['mailbody_trash_path'], $config['mailbody_file_prefix']);
						$dlog_currmess->addItem('MESSAGE_BODY_SAVED',dlog::ITEM_TYPE_INFO,$next_filename);
						$mailboxer->delete($current_msgno);
						$dlog_currmess->addItem('MESSAGE_DELETED',dlog::ITEM_TYPE_INFO);
						$user_messages->add('MESSAGE_DELETED','NOTICE');

						// commentend, cos we dont sending message to user if login not found
						//$user_messages->send_messages();
						continue;
					}
					else
					{
						$user_messages->add('FOUNDED_LOGIN','NOTICE',$login);
					}


				// password
					if($passwd === false)
					{
						$dlog_currmess->addItem('PASSWORD_NOT_FOUND',dlog::ITEM_TYPE_ERROR);
						$user_messages->add('NO_PASSWD','ERROR');
						$next_filename = $mailboxer->save_file($current_msgno, $config['mailbody_trash_path'], $config['mailbody_file_prefix']);
						$dlog_currmess->addItem('MESSAGE_BODY_SAVED',dlog::ITEM_TYPE_INFO,$next_filename);
						$mailboxer->delete($current_msgno);
						$dlog_currmess->addItem('MESSAGE_DELETED',dlog::ITEM_TYPE_INFO);
						$user_messages->add('MESSAGE_DELETED','NOTICE');

						// commented, cos we dont sending message to us
						// $user_messages->send_messages();
						continue;
					}
					else
					{
						$user_messages->add('FOUNDED_PASSWD','NOTICE',$passwd);
					}


				// ofrom
					if($ofrom === false)
					{
						$dlog_currmess->addItem('OFROM_NOT_FOUND_AND_DEFAULT USED',dlog::ITEM_TYPE_WARNING,$ofrom_default);
						$user_messages->add('NO_OFROM','WARNING');
						$user_messages->add('USED_DEFAULT_OFROM','NOTICE',$ofrom_default);
						$ofrom = $ofrom_default;
					}
					else
					{
						$user_messages->add('FOUNDED_OFROM','NOTICE',$ofrom);

						switch(rueng2eng($ofrom)):
							case 'bn':
							case 'es':
								break;
							default:
								$dlog_currmess->addItem('OFROM_NOT_SUPPORTED_AND_DEFAULT_USED',dlog::ITEM_TYPE_WARNING,$ofrom_default);
								$user_messages->add('NOT_SUPPORTED_OFROM','WARNING',$ofrom);
								$user_messages->add('USED_DEFAULT_OFROM','NOTICE',$ofrom_default);
								$ofrom = $ofrom_default;
						endswitch;
					}


				// format
					if($format === false)
					{
						$dlog_currmess->addItem('FORMAT_NOT_FOUND',dlog::ITEM_TYPE_ERROR);
						$next_filename = $mailboxer->save_file($current_msgno, $config['mailbody_trash_path'], $config['mailbody_file_prefix']);
						$dlog_currmess->addItem('MESSAGE_BODY_SAVED',dlog::ITEM_TYPE_INFO,$next_filename);
						$mailboxer->delete($current_msgno);
						$dlog_currmess->addItem('MESSAGE_DELETED',dlog::ITEM_TYPE_INFO);
						$user_messages->add('MESSAGE_DELETED','NOTICE');
						$user_messages->send_messages($queue_params);
						continue;
					}
					else
					{
						$user_messages->add('FOUNDED_FORMAT','NOTICE',$format);

						switch(rueng2eng($format)):
							case 'bn':
							case 'nevdom':
								break;
							default:
								$dlog_currmess->addItem('FORMAT_NOT_SUPPORTED',dlog::ITEM_TYPE_ERROR,$format);
								$user_messages->add('NOT_SUPPORTED_FORMAT','ERROR',$format);
								$mailboxer->delete($current_msgno);
								$dlog_currmess->addItem('MESSAGE_DELETED',dlog::ITEM_TYPE_INFO);
								$user_messages->add('MESSAGE_DELETED','NOTICE');
								//trig_error(NOTICE_WILL_TRY_RECOGNIZE_FORMAT);
								//$format = 'RECOGNIZE';
								$user_messages->send_messages($queue_params);
								continue 2;
						endswitch;
					}


				// testing login and password
					$dlog_currmess->addItem('CHECKING_AUTHORIZATION',dlog::ITEM_TYPE_INFO);
					if($user_id = checkLogin($login,$passwd))
					{
						$dlog_currmess->addItem('AUTHORIZATION_SUCCESS',dlog::ITEM_TYPE_INFO);
						$authorized = true;
						$user_messages->add('AUTHORIZATION_SUCCESS','NOTICE');
					}
					else
					{
						$dlog_currmess->addItem('AUTHORIZATION_FAILED',dlog::ITEM_TYPE_ERROR);
						$authorized = false;
						$user_messages->add('AUTHORIZATION_FAILED','ERROR',$login,$passwd);
						$next_filename = $mailboxer->save_file($current_msgno, $config['mailbody_trash_path'], $config['mailbody_file_prefix']);
						$dlog_currmess->addItem('MESSAGE_BODY_SAVED',dlog::ITEM_TYPE_INFO,$next_filename);
						$mailboxer->delete($current_msgno);
						$dlog_currmess->addItem('MESSAGE_DELETED',dlog::ITEM_TYPE_INFO);
						$user_messages->add('MESSAGE_DELETED','NOTICE');
						$user_messages->send_messages($queue_params);
						continue;
					}
			//BLOCKEND}}}

			### IF WE ARE HERE - NO LOGIN ERRORS WERE, SO WE CAN WRITE COMPLETE QUEUE ###

			//BLOCK: PREPARING QUEUE XML{{{
				$dlog_xmlprep = $dlog_currmess->addGroup('PREPARING_QUEUE_XML');

				$xml = new DOMDocument('1.0','koi8-r');
				$xml->formatOutput = true;
				$xml_root = $xml->appendChild($xml->createElement('root'));
				$dlog_xmlprep->addItem('QUEUE_XML_CREATING_QUEUE_NODE',dlog::ITEM_TYPE_INFO);
				$xml_queue = $xml_root->appendChild($xml->createElement('queue'));
				$xml_queue->setAttribute('date',date('Y-m-d H:i:s'));

				$dlog_xmlprep->addItem('QUEUE_XML_CREATING_INPUTINFO_NODE',dlog::ITEM_TYPE_INFO);
				$xml_inputinfo = $xml_queue->appendChild($xml->createElement('inputinfo'));
				$xml_inputinfo->setAttribute('receive_type','mail');
				$xml_inputinfo->setAttribute('sender_addr',utf($msg_info->from_addr));
				$xml_inputinfo->setAttribute('sender_name',utf($msg_info->from_name));
				$xml_inputinfo->setAttribute('subject',utf($msg_info->subject));

				$dlog_xmlprep->addItem('QUEUE_XML_CREATING_AUTH_NODE',dlog::ITEM_TYPE_INFO);
				$xml_auth = $xml_queue->appendChild($xml->createElement('auth'));
				$xml_auth->setAttribute('login',utf($login));
				$xml_auth->setAttribute('passwd',utf($passwd));
				$xml_auth->setAttribute('user_id',utf($user_id));

				$dlog_xmlprep->addItem('QUEUE_XML_CREATING_STATUS_NODE',dlog::ITEM_TYPE_INFO);
				$xml_status = $xml_queue->appendChild($xml->createElement('status'));
				$xml_stage = $xml_status->appendChild($xml->createElement('stage'));
				$xml_stage->setAttribute('name','mail_parsing');


				$dlog_xmlprep->addItem('QUEUE_XML_CREATING_QUEUEBODY_NODE',dlog::ITEM_TYPE_INFO);
				$xml_queuebody = $xml_queue->appendChild($xml->createElement('queuebody'));
				$xml_queuebody->setAttribute('format',utf(strtolower($format)));
				$xml_queuebody->setAttribute('ofrom',utf(strtolower($ofrom)));
			//BLOCKEND}}}

			//BLOCK: EXTRACTING FILES FROM MESSAGE {{{
				$dlog_extractfiles = $dlog_xmlprep->addGroup('EXTRACTING_FILES_FROM_MESSAGE');

				$tmp_count_extracted_files = 0;

				foreach($msg_info->parts as $part_num => $part)
				{
					$dlog_extractfiles->addItem('TESTING_MESSAGE_PART',dlog::ITEM_TYPE_INFO,$part_num);
					if(isset($part->filename))
					{
						$dlog_extractfiles->addItem('PART_IDENTIFIED_AS_FILE_ATTACHMENT',dlog::ITEM_TYPE_INFO);
						$item = $xml_queuebody->appendChild($xml->createElement('item'));
						$item->setAttribute('type',$part->type);
						$item->setAttribute('subtype',$part->subtype);

						$item->setAttribute('filename',utf($part->filename));

						$dlog_extractfiles->addItem('ADDING_ATTACHMENT_FILE_TO_XML',dlog::ITEM_TYPE_INFO,$part->filename);
						$item_cdata = $item->appendChild($xml->createCDATASection(base64_encode($part->content)));

						$tmp_count_extracted_files++;
					}
					else
					{
						$dlog_extractfiles->addItem('PART_IDENTIFIED_AS_NOT_FILE_ATTACHMENT',dlog::ITEM_TYPE_WARNING);
					}
				}

				$user_messages->add('FILES_EXTRACTED','NOTICE',$tmp_count_extracted_files);
				$dlog_extractfiles->addItem('FILES_EXTRACTED',dlog::ITEM_TYPE_INFO,$tmp_count_extracted_files);
			//BLOCKEND}}}

			//BLOCK: SAVING MESSAGE BODY AND DELETING MESSAGE FROM MAIL SERVER {{{
				$next_filename = $mailboxer->save_file($current_msgno, $config['mailbody_complete_path'], $config['mailbody_file_prefix']);
				$dlog_currmess->addItem('MESSAGE_BODY_SAVED',dlog::ITEM_TYPE_INFO,$next_filename);
				$mailboxer->delete($current_msgno);
				$dlog_currmess->addItem('MESSAGE_DELETED',dlog::ITEM_TYPE_INFO);
				$user_messages->add('MESSAGE_DELETED','NOTICE');
			//BLOCKEND}}}

			//BLOCK: SAVING USER MESSAGES TO XML {{{
				$dlog_xmlprep->addItem('QUEUE_XML_IMPORTING_STATUS_INTO_STATUS_NODE',dlog::ITEM_TYPE_INFO);

				foreach($user_messages->get_messages() as $user_message)
				{
					$item = $xml_stage->appendChild($xml->createElement('item'));
					$item->setAttribute('type',utf($user_message[0]));
					$item->setAttribute('content',utf($user_message[1]));

					unset($user_message[0],$user_message[1]);
					foreach($user_message as $part)
					{
						$param = $item->appendChild($xml->createElement('param'));
						$param->setAttribute('content',utf($part));
					}
				}
			//BLOCKEND}}}

			//BLOCK: SAVING QUEUE XML {{{
				$nextname = getNextName($config['queue_path'], $config['queue_file_prefix'], 'xml', $config['queue_file_digits']);

				if($xml->save($nextname))
				{
					$dlog_xmlprep->addItem('XML_QUEUE_SAVED',dlog::ITEM_TYPE_INFO,$nextname);
				}
				else
				{
					$dlog_xmlprep->addItem('XML_QUEUE_SAVE_FAILED');
				}
			//BLOCKEND}}}
	}

	$dlog->savelog($config['mail_worklog_file_prefix'],$config['mail_worklog_file_suffix'],$config['mail_worklog_file_path']);
}
?>