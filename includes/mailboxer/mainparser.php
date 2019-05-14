#!/usr/local/bin/php
<?php
$config = parse_ini_file('/home/bsnrobot/configuration.ini');

// limit for testing file charset (0 - no limit)
define('CHARSET_TEST_LIMIT', 1000);

error_reporting(E_ALL);

// managing execution properties //{{{
	$DELETE = false;
	$QUIET = true;
	$ENABLE_PLAIN_BN_ALL = false;
	$ENABLE_PLAIN_BN_NED = false;
	$ENABLE_PLAIN_BN_KN = false;
	$ENABLE_PLAIN_BN_ZDD = false;
	$ENABLE_PLAIN_BN_ARD = false;
	$ENABLE_NEVDOM_LIVE_RENT = false;

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
			case 'enable_plain_bn_all':
				$ENABLE_PLAIN_BN_ALL = true;
				break;
			case 'enable_plain_bn_ned':
				$ENABLE_PLAIN_BN_NED = true;
				break;
			case 'enable_plain_bn_kn':
				$ENABLE_PLAIN_BN_KN = true;
				break;
			case 'enable_plain_bn_zdd':
				$ENABLE_PLAIN_BN_ZDD = true;
				break;
			case 'enable_plain_bn_ard':
				$ENABLE_PLAIN_BN_ARD = true;
				break;
			case 'enable_nevdom_live_rent':
				$ENABLE_NEVDOM_LIVE_RENT = true;
				break;
			case 'help':
				echo "\n";
				echo "  USAGE: \n";
				echo "    ./mainparser.php [options]\n";
				echo "\n";
				echo "  AVAILABLE OPTIONS:\n";
				echo "    --delete (delete parsed queue?, default: \"no\")\n";
				echo "    --quiet (dont output anything to stdout?, default: \"yes\")\n";
				echo "    --enable_plain_bn_all (enable this parser?)\n";
				echo "    --enable_plain_bn_ned (enable this parser?)\n";
				echo "    --enable_plain_bn_kn (enable this parser?)\n";
				echo "    --enable_plain_bn_zdd (enable this parser?)\n";
				echo "    --enable_plain_bn_ard (enable this parser?)\n";
				echo "    --enable_nevdom_live_rent (enable this parser?)\n";
				echo "    --help (this screen)\n";
				echo "\n";
				echo "  NOTICE:\n";
				echo "    \"--delete and --quiet\" can be \"yes\" or \"no\"\n";
				echo "\n";
				die();
				break;
		}
	} //}}}


// REQUIREIES {{{
	// require database class
		require('/home/apache/htdocs/includes/db/mysql.core.php');
		$db->query('set names koi8r');

	// require mailer class
		require('/home/dither/lib/mailer.class.php');
		$mailer = new mailer;

	// require errors trigs
		require('/home/bsnrobot/user_messages.class.php');

	// require all utils
		require('/home/bsnrobot/parsers/utils.php');

	// require utf function
		require('/home/bsnrobot/function_utf.php');

	// require dlog class
		require('/home/bsnrobot/dlog.class');

// }}}


// GET ALL QUEUE FILES
	function getQueue() // {{{
	{
		global $config;

		$contents = scandir($config['queue_path']);

		$files = array();
		foreach($contents as $item)
		{
			if(preg_match("/^" . $config['queue_file_prefix'] . "([0-9]{" . $config['queue_file_digits'] . "})\." . $config['queue_file_extension'] . "$/",$item,$matches))
			{
				array_push($files, $config['queue_path'] . '/' . $matches[0]);
			}
			else
			{
				continue;
			}
		}

		return $files;
	} // }}}

// GET NEXT FILENAME IN TARGET QUEUE DIRECTORY
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

// GRAB ALL STATUS NODES
	function grabStatus(&$node) // {{{
	{
		$status = array();

		$childs = $node->childNodes;
		foreach($childs as $child)
		{
			$params = array();
			$index = 1;
			while(true)
			{
				if($child->hasAttribute('param' . $index))
				{
					array_push($params, $child->getAttribute('param' . $index));
					$index++;
				}
				else
				{
					break;
				}
			}
			array_push($status,$params);
		}

		return $status;
	} // }}}


function grabPrevStageStatuses(&$node)
{
	$childs = $node->childNodes;
	return $childs;
}


function node_by_name($name,$multiple = false)
{
	global $xml;

	$nodes = $xml->getElementsByTagName($name);

	if($multiple)
	{
		foreach($nodes as $node)
		{
			$nodes_ar[] = $node;
		}
		return $nodes_ar;
	}
	else
	{
		return $xml->getElementsByTagName($name)->item(0);
	}
}

$dlog = new dlog('koi8-r','koi8-r');
$dlog_queuewalk = $dlog->addGroup('FETCHING QUEUE');

foreach(getQueue() as $idx => $queue_filename)
{
	$user_messages = new user_messages_sender;
	if(!$QUIET)
	{
		echo $queue_filename."\n";
	}

	$dlog_file = $dlog->addGroup('PROCESSING_QUEUE_FILE');
	$dlog_file->addItem('CURRENT_FILE', dlog::ITEM_TYPE_INFO, $idx, $queue_filename);

	// loading one queue file
		$xml = new DOMDocument('1.0','utf-8');
		$xml->formatOutput = true;
		$xml->preserveWhiteSpace = false;
		$xml->load($queue_filename);

	// determining main root and queue nodes
		$root_node = $xml->firstChild;
		$queue_node = $root_node->firstChild;
		$inputinfo_node = node_by_name('inputinfo');
		$queuebody_node = node_by_name('queuebody');
		$status_node = node_by_name('status');

	// receive type fetching
		$dlog_receive_type = $dlog_file->addGroup('RECEIVE_TYPE');
		switch($inputinfo_node->getAttribute('receive_type')):
			case 'mail':
				$mailinfo_node = node_by_name('mailinfo');

				// params used by user_message_sender class
					$queue_params = new stdClass;
					$queue_params->type = 'mail';
					$queue_params->from_addr = koi($inputinfo_node->getAttribute('sender_addr'));
					$queue_params->from_name = koi($inputinfo_node->getAttribute('sender_name'));
					$queue_params->subject = koi($inputinfo_node->getAttribute('subject'));
					$queue_params->current_stage = 'file_parsing';
					$queue_params->date_fetched = $queue_node->getAttribute('date');
					$queue_params->prev_status_node =& $status_node;

					$dlog_receive_type->addItem('TYPE', dlog::ITEM_TYPE_INFO, $queue_params->type);
					$dlog_receive_type->addItem('FROM_ADDR', dlog::ITEM_TYPE_INFO, $queue_params->from_addr);
					$dlog_receive_type->addItem('FROM_NAME', dlog::ITEM_TYPE_INFO, $queue_params->from_name);
					$dlog_receive_type->addItem('SUBJECT', dlog::ITEM_TYPE_INFO, $queue_params->subject);
				break;
		endswitch;



	switch($queuebody_node->getAttribute('format')):
		case 'bn':
			$dlog_file->addItem('FILE_FORMAT', dlog::ITEM_TYPE_INFO, 'bn');

			// determining all needed nodes in queue
				$auth_node = node_by_name('auth');
				$prev_stages = node_by_name('stage',true);

			// stops if user_id not specified in queue
				$user_id = $auth_node->getAttribute('user_id');
				$dlog_file->addItem('USER_ID', dlog::ITEM_TYPE_INFO, $user_id);

				if(empty($user_id))
				{
					$dlog_file->addItem('NO_USER_ID_SKIPPING ', dlog::ITEM_TYPE_INFO);

					$user_messages->add('USER_ID_NOT_FOUND','ERROR');
					$user_messages->add('QUEUE_DELETED','NOTICE');
					$user_messages->send_messages($queue_params);
					continue 2;
				}


			// preparing result xml
				$dlog_file->addItem('PREPARING_RESULT_XML', dlog::ITEM_TYPE_INFO);

				$xmlresult = new DOMDocument('1.0','koi8-r');
				$xmlresult->formatOutput = true;

				$xmlresult_root = $xmlresult->appendChild($xmlresult->createElement('root'));
				$xmlresult_queue = $xmlresult_root->appendChild($xmlresult->createElement('queue'));
				$xmlresult_queue->setAttribute('recv_type',$queue_node->getAttribute('recv_type'));
				$xmlresult_queue->setAttribute('parse_type','plain_bn');
				$xmlresult_queue->setAttribute('date',date('Y-m-d H:i:s'));

				// importing nodes
					switch($inputinfo_node->getAttribute('receive_type')):
						case 'mail':
							$xmlresult_queue->appendChild($xmlresult->importNode($inputinfo_node,true));
							$xmlresult_queue->appendChild($xmlresult->importNode($auth_node,true));
							break;
					endswitch;

				$xmlresult_status = $xmlresult_queue->appendChild($xmlresult->createElement('status'));

				// sending previus status messages back into queue
				foreach($prev_stages as $prev_stage)
				{
					$xmlresult_status->appendChild($xmlresult->importNode($prev_stage,true));
				}

				$xmlresult_queuebody = $xmlresult_queue->appendChild($xmlresult->createElement('queuebody'));
				$xmlresult_queuebody->setAttribute('format',$queuebody_node->getAttribute('format'));
				$xmlresult_queuebody->setAttribute('ofrom',$queuebody_node->getAttribute('ofrom'));


			// adding this status stage
				$xmlresult_stage = $xmlresult_status->appendChild($xmlresult->createElement('stage'));
				$xmlresult_stage->setAttribute('name','data_parsing');


			$dlog_nodes = $dlog_file->addGroup('WALKING_THROUGHT_BASE64_NODES');
			foreach($queuebody_node->childNodes as $child) // walking on all base64 files in queue file
			{
				$dlog_node = $dlog_nodes->addGroup('PROCESSING_ATTACHMENT');
				$dlog_node->addItem('ATTACHMENT_FILENAME', dlog::ITEM_TYPE_INFO, $child->getAttribute('filename'));

				$user_messages->add('WORKING_ON_ATTACHMENT','NOTICE',$child->getAttribute('filename'));
				$type = (int) $child->getAttribute('type');
				$subtype = $child->getAttribute('subtype');

				// initial value
					$parsed_ok = false;

				if($type === 0 && $subtype == 'PLAIN')
				{
					$filename = strtolower($child->getAttribute('filename'));
					switch($filename):
						case 'all.txt': // live_sell
						case 'zhil.txt': // live_sell
							if($ENABLE_PLAIN_BN_ALL)
							{
								$dlog_node->addItem('QUEUEPART_TYPE', dlog::ITEM_TYPE_INFO, 'live-sell');
								$queuepart_type = 'live-sell';
								$user_messages->add('PARSE_TYPE_RECOGNIZED','NOTICE','Жилая недвижимость, продажа');
								$dlog_node->addItem('APPLYING_PARSER', dlog::ITEM_TYPE_INFO, $config['parsers_path'] . '/' . $config['parser_plain_bn_all']);
								$dparser = new stdClass;
								$dparser->srcfilename = $filename;
								$dparser->content = base64_decode($child->nodeValue);
								require($config['parsers_path'] . '/' . $config['parser_plain_bn_all']);
								$parsed_ok = true;
							}
							else
							{
								$dlog_node->addItem('PLAIN_BN_ALL_DISABLED', dlog::ITEM_TYPE_WARNING);
								$parsed_ok = false;
								$user_messages->add('PARSE_TYPE_DISABLED','ERROR');
							}
							break;
						case 'ned.txt': // build_flats
							if($ENABLE_PLAIN_BN_NED)
							{
								$dlog_node->addItem('QUEUEPART_TYPE', dlog::ITEM_TYPE_INFO, 'build');
								$queuepart_type = 'build';
								$user_messages->add('PARSE_TYPE_RECOGNIZED','NOTICE','Строящаяся недвижимость');
								$dlog_node->addItem('APPLYING_PARSER', dlog::ITEM_TYPE_INFO, $config['parsers_path'] . '/' . $config['parser_plain_bn_ned']);
								$dparser = new stdClass;
								$dparser->srcfilename = $filename;
								$dparser->content = base64_decode($child->nodeValue);
								require($config['parsers_path'] . '/' . $config['parser_plain_bn_ned']);
								$parsed_ok = true;
							}
							else
							{
								$dlog_node->addItem('PLAIN_BN_NED_DISABLED', dlog::ITEM_TYPE_WARNING);
								$parsed_ok = false;
								$user_messages->add('PARSE_TYPE_DISABLED','ERROR');
							}
							break;
						case 'kn.txt': // commercial_sell, commercial_rent
							if($ENABLE_PLAIN_BN_KN)
							{
								$dlog_node->addItem('QUEUEPART_TYPE', dlog::ITEM_TYPE_INFO, 'commercial');
								$queuepart_type = 'commercial';
								$user_messages->add('PARSE_TYPE_RECOGNIZED','NOTICE','Коммерческая недвижимость');
								$dlog_node->addItem('APPLYING_PARSER', dlog::ITEM_TYPE_INFO, $config['parsers_path'] . '/' . $config['parser_plain_bn_kn']);
								$dparser = new stdClass;
								$dparser->srcfilename = $filename;
								$dparser->content = base64_decode($child->nodeValue);
								require($config['parsers_path'] . '/' . $config['parser_plain_bn_kn']);
								$parsed_ok = true;
							}
							else
							{
								$dlog_node->addItem('PLAIN_BN_KN_DISABLED', dlog::ITEM_TYPE_WARNING);
								$parsed_ok = false;
								$user_messages->add('PARSE_TYPE_DISABLED','ERROR');
							}
							break;
						case 'zdd.txt': // country_sell
							if($ENABLE_PLAIN_BN_ZDD)
							{
								$dlog_node->addItem('QUEUEPART_TYPE', dlog::ITEM_TYPE_INFO, 'country-sell');
								$queuepart_type = 'country-sell';
								$user_messages->add('PARSE_TYPE_RECOGNIZED','NOTICE','Загородная недвижимость, продажа');
								$dlog_node->addItem('APPLYING_PARSER', dlog::ITEM_TYPE_INFO, $config['parsers_path'] . '/' . $config['parser_plain_bn_zdd']);
								$dparser = new stdClass;
								$dparser->srcfilename = $filename;
								$dparser->content = base64_decode($child->nodeValue);
								require($config['parsers_path'] . '/' . $config['parser_plain_bn_zdd']);
								$parsed_ok = true;
							}
							else
							{
								$dlog_node->addItem('PLAIN_BN_ZDD_DISABLED', dlog::ITEM_TYPE_WARNING);
								$parsed_ok = false;
								$user_messages->add('PARSE_TYPE_DISABLED','ERROR');
							}
							break;
						case 'ard.txt': // live_rent, country_rent
							if($ENABLE_PLAIN_BN_ARD)
							{
								$dlog_node->addItem('QUEUEPART_TYPE', dlog::ITEM_TYPE_INFO, 'live-rent');
								$queuepart_type = 'live-rent';
								$user_messages->add('PARSE_TYPE_RECOGNIZED','NOTICE','Жилая недвижимость, аренда');
								$dlog_node->addItem('APPLYING_PARSER', dlog::ITEM_TYPE_INFO, $config['parsers_path'] . '/' . $config['parser_plain_bn_ard']);
								$dparser = new stdClass;
								$dparser->srcfilename = $filename;
								$dparser->content = base64_decode($child->nodeValue);
								require($config['parsers_path'] . '/' . $config['parser_plain_bn_ard']);
								$parsed_ok = true;
							}
							else
							{
								$dlog_node->addItem('PLAIN_BN_ARD_DISABLED', dlog::ITEM_TYPE_WARNING);
								$parsed_ok = false;
								$user_messages->add('PARSE_TYPE_DISABLED','ERROR');
							}
							break;
						default:
							$dlog_node->addItem('QUEUEPART_TYPE', dlog::ITEM_TYPE_INFO, 'not_recognized');
							$user_messages->add('PARSE_TYPE_NOT_RECOGNIZED','ERROR');
							continue 1;
					endswitch;
				}

				if($parsed_ok)
				{
					$dparser_estatedata_node = $dparser->result->firstChild->firstChild;
					$all_items = $dparser_estatedata_node->childNodes;
					if($all_items->length > 0)
					{
						$xmlresult_queuebody_queuepart = $xmlresult_queuebody->appendChild($xmlresult->createElement('queuepart'));
						$xmlresult_queuebody_queuepart->setAttribute('type',$queuepart_type);

						foreach($all_items as $item)
						{
							$xmlresult_queuebody_queuepart->appendChild($xmlresult->importNode($item,true));
						}
					}
					$dlog_node->addItem('PARSED_ITEMS_COUNT', dlog::ITEM_TYPE_INFO, $all_items->length);
				}
			}
			break;

		case 'nevdom':
			$dlog_file->addItem('FILE_FORMAT', dlog::ITEM_TYPE_INFO, 'nevdom');

			// determining all needed nodes in queue
				$auth_node = node_by_name('auth');
				$prev_stages = node_by_name('stage',true);

			// stops if user_id not specified in queue
				$user_id = $auth_node->getAttribute('user_id');
				$dlog_file->addItem('USER_ID', dlog::ITEM_TYPE_INFO, $user_id);

				if(empty($user_id))
				{
					$dlog_file->addItem('NO_USER_ID_SKIPPING ', dlog::ITEM_TYPE_INFO);

					$user_messages->add('USER_ID_NOT_FOUND','ERROR');
					$user_messages->add('QUEUE_DELETED','NOTICE');
					$user_messages->send_messages($queue_params);
					continue 2;
				}


			// preparing result xml
				$dlog_file->addItem('PREPARING_RESULT_XML', dlog::ITEM_TYPE_INFO);

				$xmlresult = new DOMDocument('1.0','koi8-r');
				$xmlresult->formatOutput = true;

				$xmlresult_root = $xmlresult->appendChild($xmlresult->createElement('root'));
				$xmlresult_queue = $xmlresult_root->appendChild($xmlresult->createElement('queue'));
				$xmlresult_queue->setAttribute('recv_type',$queue_node->getAttribute('recv_type'));
				$xmlresult_queue->setAttribute('parse_type','nevdom');
				$xmlresult_queue->setAttribute('date',date('Y-m-d H:i:s'));

				// importing nodes
					switch($inputinfo_node->getAttribute('receive_type')):
						case 'mail':
							$xmlresult_queue->appendChild($xmlresult->importNode($inputinfo_node,true));
							$xmlresult_queue->appendChild($xmlresult->importNode($auth_node,true));
							break;
					endswitch;

				$xmlresult_status = $xmlresult_queue->appendChild($xmlresult->createElement('status'));

				// sending previus status messages back into queue
				foreach($prev_stages as $prev_stage)
				{
					$xmlresult_status->appendChild($xmlresult->importNode($prev_stage,true));
				}

				$xmlresult_queuebody = $xmlresult_queue->appendChild($xmlresult->createElement('queuebody'));
				$xmlresult_queuebody->setAttribute('format',$queuebody_node->getAttribute('format'));
				$xmlresult_queuebody->setAttribute('ofrom',$queuebody_node->getAttribute('ofrom'));


			// adding this status stage
				$xmlresult_stage = $xmlresult_status->appendChild($xmlresult->createElement('stage'));
				$xmlresult_stage->setAttribute('name','data_parsing');


			$dlog_nodes = $dlog_file->addGroup('WALKING_THROUGHT_BASE64_NODES');
			$dlog_nodes->addItem('BASE64_NODES_COUNT', dlog::ITEM_TYPE_INFO, $queuebody_node->childNodes->length);
			foreach($queuebody_node->childNodes as $child) // walking on all base64 files in queue file
			{
				$dlog_node = $dlog_nodes->addGroup('PROCESSING_ATTACHMENT');
				$dlog_node->addItem('ATTACHMENT_FILENAME', dlog::ITEM_TYPE_INFO, $child->getAttribute('filename'));

				$user_messages->add('WORKING_ON_ATTACHMENT','NOTICE',$child->getAttribute('filename'));
				$type = (int) $child->getAttribute('type');
				$subtype = $child->getAttribute('subtype');

				// initial value
					$parsed_ok = false;

				if($type === 0 && $subtype == 'PLAIN')
				{
					$filename = strtolower($child->getAttribute('filename'));
					switch($filename):
						case 'ard.txt': // live_rent
							if($ENABLE_NEVDOM_LIVE_RENT)
							{
								$dlog_node->addItem('QUEUEPART_TYPE', dlog::ITEM_TYPE_INFO, 'live-rent');
								$queuepart_type = 'live-rent';
								$user_messages->add('PARSE_TYPE_RECOGNIZED','NOTICE','Жилая недвижимость, аренда');
								$dlog_node->addItem('APPLYING_PARSER', dlog::ITEM_TYPE_INFO, $config['parsers_path'] . '/' . $config['parser_nevdom_live_rent']);
								$dparser = new stdClass;
								$dparser->srcfilename = $filename;
								$dparser->content = base64_decode($child->nodeValue);
								require($config['parsers_path'] . '/' . $config['parser_nevdom_live_rent']);
								$parsed_ok = true;
							}
							else
							{
								$dlog_node->addItem('NEVDOM_LIVE_RENT_DISABLED', dlog::ITEM_TYPE_WARNING);
								$user_messages->add('PARSE_TYPE_DISABLED','ERROR');
							}
							break;
						default:
							$dlog_node->addItem('QUEUEPART_TYPE', dlog::ITEM_TYPE_INFO, 'not_recognized');
							$parsed_ok = false;
							$user_messages->add('PARSE_TYPE_NOT_RECOGNIZED','ERROR');
							continue 1;
					endswitch;
				}

				if($parsed_ok)
				{
					$dparser_estatedata_node = $dparser->result->firstChild->firstChild;
					$all_items = $dparser_estatedata_node->childNodes;
					if($all_items->length > 0)
					{
						$xmlresult_queuebody_queuepart = $xmlresult_queuebody->appendChild($xmlresult->createElement('queuepart'));
						$xmlresult_queuebody_queuepart->setAttribute('type',$queuepart_type);

						foreach($all_items as $item)
						{
							$xmlresult_queuebody_queuepart->appendChild($xmlresult->importNode($item,true));
						}
					}
					$dlog_node->addItem('PARSED_ITEMS_COUNT', dlog::ITEM_TYPE_INFO, $all_items->length);
				}
			}
			break;

		default:
			$dlog_file->addItem('FILE_FORMAT', dlog::ITEM_TYPE_INFO, 'not_supported', $queuebody_node->getAttribute('format'));
			$parsed_ok = false;
			$user_messages->add('NOT_SUPPORTED_FORMAT','ERROR',$queuebody_node->getAttribute('format'));
			break;
	endswitch;


	if($DELETE)
	{
		unlink($queue_filename);
	}

	if(!isset($dparser)) // Ни одного файла не обработалось
	{
		$user_messages->send_messages($queue_params);
	}
	else if ($xmlresult_queuebody->hasChildNodes())
	{
		foreach($user_messages->get_messages() as $user_message)
		{
			$item = $xmlresult_stage->appendChild($xmlresult->createElement('item'));
			$item->setAttribute('type',utf($user_message[0]));
			$item->setAttribute('content',utf($user_message[1]));
			unset($user_message[0],$user_message[1]);

			foreach($user_message as $tmp)
			{
				$param = $item->appendChild($xmlresult->createElement('param'));
				$param->setAttribute('content',utf($tmp));
			}
		}


		// finding last filename
			$nextfile = getNextName($config['final_queue_path'], $config['final_queue_file_prefix'], $config['final_queue_file_extension'], $config['final_queue_file_digits']);

		$xmlresult->save($nextfile);
		unset($dparser);
	}
	else
	{
		$user_messages->send_messages($queue_params);
	}
}

$dlog->savelog($config['parser_worklog_file_prefix'],$config['parser_worklog_file_suffix'],$config['parser_worklog_file_path']);
?>
