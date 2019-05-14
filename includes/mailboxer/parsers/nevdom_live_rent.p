<?php	
// including commmon function library
	require_once('parsers_common.p');
	
// log variable
	$dlogp = new dlog('koi8-r','koi8-r');
	$dlogp_process = $dlogp->addGroup('RUNNING_PARSER');
	$dlogp_process->addItem('PARSER', dlog::ITEM_TYPE_INFO, 'nevdom_live_rent.p');
	
// charset detection
	$charset = detect_cyr_charset($dparser->content, CHARSET_TEST_LIMIT);
	$dlogp_process->addItem('SOURCE_CHARSET', dlog::ITEM_TYPE_INFO, $charset);

// encoding received file (alt-koi => koi8-r)
	$dparser->content = @iconv($charset,'koi8-r',$dparser->content);
	$dlogp_process->addItem('CONVERTING_SOURCE', dlog::ITEM_TYPE_INFO);

// exploding file content by lines
	$dparser->content = str_replace("\r\n", "\n", $dparser->content);
	$bylines = explode("\n",$dparser->content);
	$dlogp_bylines = $dlogp_process->addGroup('EXPLODING_BY_LINES');
	$dlogp_bylines->addItem('LINES_COUNT', dlog::ITEM_TYPE_INFO, sizeof($bylines));

// creating new xml document for parsed results
	$dparser->result = new DOMDocument('1.0','koi8-r');

// creating link for $xml (что бы не писать постоянно dparser->result =))
	$xml =& $dparser->result;
	$xml->formatOutput = true;

// creating common nodes in final xml
	$root_node = $xml->appendChild($xml->createElement('root'));
	$estatedata_node = $root_node->appendChild($xml->createElement('estatedata'));
	$estatedata_node->setAttribute('type','live_rent');

// default values for processed/ignored lines
	$processed_total = 0;
	$ignored_lines = 0;

// parsing each line
$dlogp_lines = $dlogp_process->addGroup('PARSING_LINES');
foreach($bylines as $linenum => $oneline)
{
	// exploding by ';'
		$elements = explode(";",$oneline);
		$total_elements = sizeof($elements);
		
		$dlogp_line = $dlogp_lines->addGroup('PARSING_LINE');
		$dlogp_line->addItem('LINE_INFO', dlog::ITEM_TYPE_INFO, $linenum, $oneline, $total_elements);

	// switching number of total fields  in line
		switch($total_elements):
			case 11:
			case 12:
				// it's ok - mode = live rent
				$mode = 'live_rent';
				$dlogp_line->addItem('PARSE_MODE', dlog::ITEM_TYPE_INFO, $mode);
				break;
			case 1: // blank line?
			default:
				// not 11 - ignoring
				$ignored_lines++;
				$dlogp_line->addItem('LINE_IGNORED', dlog::ITEM_TYPE_INFO);
				continue 2;
		endswitch;

	// switching target mode (determined by fields count)
	switch($mode):
		case 'live_rent':
			// liv_rent nevdom format
			$dlogp_conv = $dlogp_line->addGroup('PROCESSING_ELEMENTS');

			// creating new temporary container for all values
				$v = new stdClass;

			// processing all values
				// rooms_count
			  $tmp = intval($elements[0]);
				$v->rooms_count = !empty($tmp) ? $tmp : NULL;
				$dlogp_conv->addItem('CONVERTING_FIELD_ROOMS_COUNT', dlog::ITEM_TYPE_INFO, $elements[0], $v->rooms_count);
				
				// block_id, addr
				$tmp = $elements[1];
				if(!preg_match("/^(.*?) р-н, (.*)$/", $tmp, $matches))
				{
					$matches[1] = $matches[2] = '-';
				}
				if ($matches[1] == 'Центральный')
					$matches[1] == 'Центр';
				
				$v->block_id = plain_parser::get_block_id($matches[1]);
				$v->addr = !empty($matches[2]) ? utf($matches[2]) : NULL;
				$dlogp_conv->addItem('CONVERTING_FIELD_BLOCK_AND_ADDR', dlog::ITEM_TYPE_INFO, $elements[1], $v->block_id, koi($v->addr));
				
				// storey, storey_total
				list($storey, $storey_total) = plain_parser::grabStoreys($elements[2]);
				$v->storey = utf($storey);
				$v->storey_total = utf($storey_total);
				$dlogp_conv->addItem('CONVERTING_FIELD_STOREY_AND_STOREY_TOTAL', dlog::ITEM_TYPE_INFO, $elements[2], $v->storey, $v->storey_total);
				
				// square_full
				$v->sqear_full = (($tmp = floatval(utf(plain_parser::eval_sqear($elements[3])))) > 0) ? $tmp : NULL;
				$dlogp_conv->addItem('CONVERTING_FIELD_SQUARE_FULL', dlog::ITEM_TYPE_INFO, $elements[3], $v->sqear_full);
				
				// square_rooms
				$tmp = $elements[4];
				$v->sqear_rooms = !empty($tmp) ? utf($tmp) : NULL;
				$dlogp_conv->addItem('CONVERTING_FIELD_SQUARE_ROOMS', dlog::ITEM_TYPE_INFO, $elements[4], $v->sqear_rooms);
				
				// square_live
				$v->sqear_live = (($tmp = floatval(utf(plain_parser::eval_sqear($tmp)))) > 0) ? $tmp : NULL;
				$dlogp_conv->addItem('CALCULATING_FIELD_SQUARE_LIVE', dlog::ITEM_TYPE_INFO, $v->sqear_live);
				
				// square_kitchen
				$v->sqear_kitchen = (($tmp = floatval(utf(plain_parser::eval_sqear($elements[5])))) > 0) ? $tmp : NULL;
				$dlogp_conv->addItem('CONVERTING_FIELD_SQUARE_KITCHEN', dlog::ITEM_TYPE_INFO, $elements[5], $v->sqear_kitchen);
				
				// phone_exist
				$v->phone_exist = utf(plain_parser::plusminus($elements[6]));
				$dlogp_conv->addItem('CONVERTING_FIELD_PHONE_EXIST', dlog::ITEM_TYPE_INFO, $elements[6], $v->phone_exist);
				
				// notes
				$v->notes = (!empty($elements[7]) ? "$elements[7]\n" : '') . (!empty($elements[10]) ? "$elements[10]\n" : '') . 
													(!empty($elements[11]) ? $elements[11] : '');
				// cost paid type
				$v->cost_paid_type = 'month';
				if (stripos($v->notes, 'ПОСУТ'))
					$v->cost_paid_type = 'day';
				
				$v->notes = !empty($v->notes) ? utf($v->notes) : NULL;
				$dlogp_conv->addItem('COMBINING_FIELD_NOTES', dlog::ITEM_TYPE_INFO, koi($v->notes));
				
				// cost
				$tmp = floatval($elements[8]);
				$v->cost = !empty($tmp) ? $elements[8] : NULL;
				$dlogp_conv->addItem('CONVERTING_FIELD_COST', dlog::ITEM_TYPE_INFO, $elements[8], $v->cost);
				
				/*
				echo "\n$oneline";
				echo "\nrooms_count: " . $v->rooms_count;
				echo "\naddr: " . iconv('utf-8', 'koi8-r', $v->addr);
				echo "\nblock_id: " . $v->block_id;
				echo "\nstorey: " . $v->storey;
				echo "\nstorey_total: " . $v->storey_total;
				echo "\nsqear_full: " . $v->sqear_full;
				echo "\nsqear_live: " . $v->sqear_live;
				echo "\nsqear_kitchen: " . $v->sqear_kitchen;
				echo "\nsqear_rooms: " . $v->sqear_rooms;
				echo "\nphone_exist: " . $v->phone_exist;
				echo "\nnotes: " . iconv('utf-8', 'koi8-r', $v->notes);
				echo "\ncost: " . $v->cost;
				echo "\n";
				*/
				
				$dlogp_line->addItem('PASSING_VALUES_INTO_ITEM_IN_FINAL_XML', dlog::ITEM_TYPE_INFO);
			
			// creating item (one-line-value in final xml)
				$item = $estatedata_node->appendChild($xml->createElement('item'));

			// passing all values from $v to xml
				$item->setAttribute('addr',$v->addr);
				$item->setAttribute('addr_street_id',NULL);
				$item->setAttribute('addr_house',NULL);
				$item->setAttribute('addr_korp',NULL);
				$item->setAttribute('block_id',$v->block_id);
				$item->setAttribute('ceiling_height',NULL);
				$item->setAttribute('confirm_status','Y');
				$item->setAttribute('cost',$v->cost);
				$item->setAttribute('cost_paid_type',$v->cost_paid_type);
				$item->setAttribute('elevator_type_id',NULL);
				$item->setAttribute('enter_type_id',NULL);
				$item->setAttribute('geyser_type_id',NULL);
				$item->setAttribute('house_type_id',NULL);
				$item->setAttribute('hotwater_type_id',NULL);
				$item->setAttribute('humans_live',NULL);
				$item->setAttribute('info_added_from','bsnrobot');
				$item->setAttribute('flat_type',NULL);
				$item->setAttribute('floor_type_id',NULL);
				$item->setAttribute('loggia_type_id',NULL);
				$item->setAttribute('notes',$v->notes);
				$item->setAttribute('phone_exist',$v->phone_exist);
				$item->setAttribute('privatize_type_id',NULL);
				$item->setAttribute('quality_type_id',NULL);
				$item->setAttribute('rooms_count',$v->rooms_count);
				$item->setAttribute('rooms_selling_count',NULL);
				$item->setAttribute('seller_name',NULL);
				$item->setAttribute('seller_phone',NULL);
				$item->setAttribute('storey',$v->storey);
				$item->setAttribute('storey_total',$v->storey_total);
				$item->setAttribute('storey_double',NULL);
				$item->setAttribute('subway_id',NULL);
				$item->setAttribute('subway_length',NULL);
				$item->setAttribute('subway_length_method_id',NULL);
				$item->setAttribute('sqear_full',$v->sqear_full);
				$item->setAttribute('sqear_live',$v->sqear_live);
				$item->setAttribute('sqear_kitchen',$v->sqear_kitchen);
				$item->setAttribute('sqear_rooms',$v->sqear_rooms);
				$item->setAttribute('toilet_type_id',NULL);
				$item->setAttribute('user_id',$user_id);
				$item->setAttribute('window_type_id',NULL);

			// swifting processed count
				$processed_total++;

			break;

	endswitch;
}

if($processed_total != 0)
// if no one line has been processed
{
	$user_messages->add('FILE_PARSED','NOTICE',$processed_total,$ignored_lines);
}
// if some lines were extracted to final xml
else
{
	$user_messages->add('FILE_PARSED_NO_DATA','WARNING');
}

$dlogp_process->addItem('PROCESSED_LINES', dlog::ITEM_TYPE_INFO, $processed_total);
$dlogp_process->addItem('IGNORED_LINES', dlog::ITEM_TYPE_INFO, $ignored_lines);

$dlogp->savelog($config['parser_parselog_file_prefix'],$config['parser_parselog_file_suffix'],$config['parser_parselog_file_path']);
?>
