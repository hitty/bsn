#!/usr/local/bin/php
<?php
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
				echo "    ./final_stage.php [options]\n";
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

//BLOCK: REQUIRIES{{{
	require('/home/apache/htdocs/includes/db/mysql.core.php');
	$db->query('set names koi8r');
	require('/home/bsnrobot/dbers/estate_live_sell.php');
	require('/home/bsnrobot/dbers/estate_live_rent.php');
	require('/home/bsnrobot/dbers/estate_build_flats.php');
	require('/home/bsnrobot/dbers/estate_commercial.php');
	require('/home/bsnrobot/dbers/estate_country_sell.php');
	require('/home/bsnrobot/dlog.class');
//BLOCKEND}}}

define('REMOVE_ALL',1);
define('REMOVE_ROBOT_ONLY',2);

$dlog = new dlog('koi8-r','koi8-r');

$config = parse_ini_file('configuration.ini');

// GET ALL QUEUE FILES
	function getQueue() // {{{
	{
		global $config;

		$contents = scandir($config['final_queue_path']);

		$files = [];
		foreach($contents as $item)
		{
			if(preg_match("/^" . $config['final_queue_file_prefix'] . "([0-9]{" . $config['final_queue_file_digits'] . "})\." . $config['final_queue_file_extension'] . "$/",$item,$matches))
			{
				array_push($files, $config['final_queue_path'] . '/' . $matches[0]);
			}
			else
			{
				continue;
			}
		}

		return $files;
	} // }}}

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

$dlog_queuewalk = $dlog->addGroup('SCANNING QUEUE FILES');

foreach(getQueue() as $idx => $filename)
{
	$dlog_file = $dlog_queuewalk->addGroup('SCANNING FILE');
	$dlog_file->addItem('CURRENT_FILE', dlog::ITEM_TYPE_INFO, $idx, $filename);

	$xml = new DOMDocument('1.0','koi8-r');
	$xml->formatOutput = true;
	$xml->preserveWhiteSpace = false;
	$xml->load($filename);


	$status = node_by_name('status');
	$prev_stages = node_by_name('stage',true);
	$queuebody = node_by_name('queuebody');
	$queueparts = node_by_name('queuepart',true);
	$auth = node_by_name('auth');


	$dlog_file->addItem('SCANNING_FILE_CONTENT', dlog::ITEM_TYPE_INFO);

	foreach($queueparts as $queuepart)
	{
		$dlog_qpart = $dlog_file->addGroup('SCANNING_FILE_PART');
		$dlog_qpart->addItem('ESTATE TYPE', dlog::ITEM_TYPE_INFO, $queuepart->getAttribute('type'));

		switch($queuepart->getAttribute('type')):
			case 'live-sell':

				echo 'LIVE-SELL:'."\n";
				// removing all robot variants
					$removed = dber_estate_live_sell_remove($auth->getAttribute('user_id'),REMOVE_ALL);
					if($removed !== false)
					{
						echo 'REMOVED: ' . $removed . ', user_id: ' . $auth->getAttribute('user_id') . "\n";
						$dlog_qpart->addItem('REMOVED VARIANTS', dlog::ITEM_TYPE_INFO, $removed, $auth->getAttribute('user_id'));
					}

				// adding new variants
					$added = 0;
					$dlog_adding = $dlog_qpart->addGroup('ADDING VARIANTS');
					foreach($queuepart->childNodes as $item)
					{
						$sql = dber_estate_live_sell($item);
						$db->query($sql) or die($db->error);
						$added += $db->affected_rows;
					}

					if($db->errno > 0)
					{
						echo $db->error;die();
					}

					echo 'ADDED:   ' . $added . ', user_id: ' . $auth->getAttribute('user_id') . "\n";
					echo "\n";

				break;
			case 'live-rent':
				echo 'LIVE-RENT:'."\n";

				// removing all robot variants
					$removed = dber_estate_live_rent_remove($auth->getAttribute('user_id'),REMOVE_ALL);
					if($removed !== false)
					{
						echo 'REMOVED: ' . $removed . ', user_id: ' . $auth->getAttribute('user_id') . "\n";
						$dlog_qpart->addItem('REMOVED VARIANTS', dlog::ITEM_TYPE_INFO, $removed, $auth->getAttribute('user_id'));
					}

				// adding new variants
					$added = 0;
					$dlog_adding = $dlog_qpart->addGroup('ADDING VARIANTS');
					foreach($queuepart->childNodes as $item)
					{
						$sql = dber_estate_live_rent($item);
						$db->query($sql) or die($db->error);
						$added += $db->affected_rows;
					}
					if($db->errno > 0)
					{
						echo $db->error;die();
					}

					echo 'ADDED:   ' . $added . ', user_id: ' . $auth->getAttribute('user_id') . "\n";
					echo "\n";

				break;
			case 'build':
				echo 'BUILD:'."\n";

				// removing all robot variants
					$removed = dber_estate_build_flats_remove($auth->getAttribute('user_id'),REMOVE_ALL);
					if($removed !== false)
					{
						echo 'REMOVED: ' . $removed . ', user_id: ' . $auth->getAttribute('user_id') . "\n";
						$dlog_qpart->addItem('REMOVED VARIANTS', dlog::ITEM_TYPE_INFO, $removed, $auth->getAttribute('user_id'));
					}

				// adding new variants
					$added = 0;
					$dlog_adding = $dlog_qpart->addGroup('ADDING VARIANTS');
					foreach($queuepart->childNodes as $item)
					{
						$sql = dber_estate_build_flats($item);
						$db->query($sql) or die($db->error);
						$added += $db->affected_rows;
					}
					if($db->errno > 0)
					{
						echo $db->error;die();
					}

					$dlog_adding->addItem('ADDED VARIANTS', dlog::ITEM_TYPE_INFO, $added, $auth->getAttribute('user_id'));
					echo 'ADDED:   ' . $added . ', user_id: ' . $auth->getAttribute('user_id') . "\n";
					echo "\n";

				break;
			case 'commercial':
				echo 'COMMERCIAL:'."\n";

				// removing all robot variants
					$removed = dber_estate_commercial_remove($auth->getAttribute('user_id'),REMOVE_ALL);
					if($removed !== false)
					{
						echo 'REMOVED: ' . $removed . ', user_id: ' . $auth->getAttribute('user_id') . "\n";
						$dlog_qpart->addItem('REMOVED VARIANTS', dlog::ITEM_TYPE_INFO, $removed, $auth->getAttribute('user_id'));
					}

				// adding new variants
					$added = 0;
					$dlog_adding = $dlog_qpart->addGroup('ADDING VARIANTS');
					foreach($queuepart->childNodes as $item)
					{
						$sql = dber_estate_commercial($item);
						$db->query($sql) or die($db->error);
						$added += $db->affected_rows;
					}
					if($db->errno > 0)
					{
						echo $db->error;die();
					}

					echo 'ADDED:   ' . $added . ', user_id: ' . $auth->getAttribute('user_id') . "\n";
					echo "\n";

				break;

			case 'country-sell':
				echo 'COUNTRY-SELL:'."\n";

				// removing all robot variants
					$removed = dber_estate_country_sell_remove($auth->getAttribute('user_id'),REMOVE_ALL);
					if($removed !== false)
					{
						echo 'REMOVED: ' . $removed . ', user_id: ' . $auth->getAttribute('user_id') . "\n";
						$dlog_qpart->addItem('REMOVED VARIANTS', dlog::ITEM_TYPE_INFO, $removed, $auth->getAttribute('user_id'));
					}

				// adding new variants
					$added = 0;
					$dlog_adding = $dlog_qpart->addGroup('ADDING VARIANTS');
					foreach($queuepart->childNodes as $item)
					{
						$sql = dber_estate_country_sell($item);
						$db->query($sql) or die($db->error);
						$added += $db->affected_rows;
					}
					if($db->errno > 0)
					{
						echo $db->error;die();
					}

					echo 'ADDED:   ' . $added . ', user_id: ' . $auth->getAttribute('user_id') . "\n";
					echo "\n";

				break;
		endswitch;
	}

	if($DELETE)
	{
		$new_filename = getNextName('/home/bsnrobot/queue/final/parsed', 'queue_', 'xml', '8');
		rename($filename,$new_filename);
	}

	$dlog->savelog($config['final_worklog_file_prefix'],$config['final_worklog_file_suffix'],$config['final_worklog_file_path']);
}
?>
