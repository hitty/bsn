<?php
/* INPUT:
$dparser->content = contens of file (usually has cp866 (alt-koi) encoding
*/

// including commmon function library
	require_once('plain_bn_common.p');

// log variable
	$dlogp = new dlog('koi8-r','koi8-r');
	$dlogp_process = $dlogp->addGroup('RUNNING_PARSER');
	$dlogp_process->addItem('PARSER', dlog::ITEM_TYPE_INFO, 'plain_bn_ned.p');

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
	$estatedata_node->setAttribute('type','build');

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
			case 15:
			case 16:
				// it's ok - mode = build
				$mode = 'build';
				$dlogp_line->addItem('PARSE_MODE', dlog::ITEM_TYPE_INFO, $mode);
				break;
			case 1: // blank line?
			default:
				// not 15 and not 16 lines - ignoring
				$ignored_lines++;
				$dlogp_line->addItem('LINE_IGNORED', dlog::ITEM_TYPE_INFO);
				continue 2;
		endswitch;

	// switching target mode (determined by fields count)
	switch($mode):
		case 'build':
			// build format
			$dlogp_conv = $dlogp_line->addGroup('PROCESSING_ELEMENTS');

			// creating new temporary container for all values
				$v = new stdClass;

			// processing all values
				$v->block_id = utf(plain_bn::get_block_id($elements[0]));
				$dlogp_conv->addItem('CONVERTING_BLOCK', dlog::ITEM_TYPE_INFO, $elements[0], $v->block_id);

				$v->rooms_count = utf($elements[1]);
				$dlogp_conv->addItem('CONVERTING_ROOMS_COUNT', dlog::ITEM_TYPE_INFO, $elements[1], $v->rooms_count);

				$v->addr = utf($elements[2]);
				$dlogp_conv->addItem('CONVERTING_ADDR', dlog::ITEM_TYPE_INFO, $elements[2], $v->addr);

				$storeys = plain_bn::grabStoreys($elements[3]);
				$v->storey_start = utf($storeys[0]);
				$v->storey_total = utf($storeys[1]);
				$dlogp_conv->addItem('CONVERTING_STOREY_START_AND_STOREY_TOTAL', dlog::ITEM_TYPE_INFO, $elements[3], koi($v->storey_start), koi($v->storey_total));

				$v->sqear_full = (($tmp = floatval(utf(plain_bn::eval_sqear($elements[4])))) > 0) ? $tmp : NULL;
				$dlogp_conv->addItem('CONVERTING_SQUARE_FULL', dlog::ITEM_TYPE_INFO, $elements[4], koi($v->sqear_full));

				$v->sqear_live = (($tmp = floatval(utf(plain_bn::eval_sqear($elements[5])))) > 0) ? $tmp : NULL;
				$dlogp_conv->addItem('CONVERTING_SQUARE_LIVE', dlog::ITEM_TYPE_INFO, $elements[5], koi($v->sqear_live));

				$v->sqear_kitchen = (($tmp = floatval(utf(plain_bn::eval_sqear($elements[6])))) > 0) ? $tmp : NULL;
				$dlogp_conv->addItem('CONVERTING_SQUARE_KITCHEN', dlog::ITEM_TYPE_INFO, $elements[6], koi($v->sqear_kitchen));

				list($tmp_subway, $tmp_subway_length, $tmp_subway_length_method) = plain_bn::get_subway_id($elements[7]);
				$v->subway_id = utf($tmp_subway);
				$v->subway_length = utf($tmp_subway_length);
				$v->subway_length_method_id = utf($tmp_subway_length_method);
				$dlogp_conv->addItem('CONVERTING_FIELD_SUBWAY_AND_SUBWAY_LENGTH_AND_SUBWAY_LENGTH_METHOD', dlog::ITEM_TYPE_INFO, $elements[7], $tmp_subway, $tmp_subway_length, $tmp_subway_length_method);

				$v->house_type_id = utf(plain_bn::get_house_type_id($elements[8]));
				$dlogp_conv->addItem('CONVERTING_HOUSE_TYPE', dlog::ITEM_TYPE_INFO, $elements[8], koi($v->house_type_id));

				$v->toilet_type_id = utf(plain_bn::get_toilet_id($elements[9]));
				$dlogp_conv->addItem('CONVERTING_TOILET_TYPE', dlog::ITEM_TYPE_INFO, $elements[9], koi($v->toilet_type_id));

				$v->seller_phone = utf(trim($elements[11]));
				$dlogp_conv->addItem('CONVERTING_SELLER_PHONE', dlog::ITEM_TYPE_INFO, $elements[11], koi($v->seller_phone));

				if(preg_match("/([0-9]+)\/м/i",$elements[12],$matches))
				{
					$mcost = $matches[1];
					$v->cost_meter = utf($matches[1]);
					$v->cost = $v->cost_meter * $v->sqear_full;
				}
				else
				{
					$v->cost = utf($elements[12] * 1000);
					$v->cost_meter = NULL;
				}
				$dlogp_conv->addItem('CONVERTING_COST_METER_AND_COST', dlog::ITEM_TYPE_INFO, $elements[12], koi($v->cost_meter), koi($v->cost));

				$v->finish_date_id = utf($elements[13]);
				$findate = $elements[13];
				if(preg_match("/^(IV|III|II|I).*?кв..*?([0-9]{4})$/",$elements[13],$matches))
				{
					switch($matches[1]):
						case 'I': $decade = 1; break;
						case 'II': $decade = 2; break;
						case 'III': $decade = 3; break;
						case 'IV': $decade = 4; break;
					endswitch;


					$result = $db->query("select id from flatdata.indfinw where year = '" . $matches[2] . "' and decade = '" . $decade . "'");
					if($db->affected_rows > 0)
					{
						list($v->finish_date_id) = $result->fetch_row();
					}

				}
				else if(preg_match("/конец ([0-9]{4})/",$elements[13],$matches))
				{
					$result = $db->query("select id from flatdata.indfinw where year = '".$matches[1]."' and decade = '4'");
					if($db->affected_rows > 0)
					{
						list($v->finish_date_id) = $result->fetch_row();
					}
				}

				else if(preg_match("/сдан/",$elements[13]))
				{
					$v->finish_date_id = 4;
				}
				else if(preg_match("/комиссия/",$elements[13]))
				{
					$v->finish_date_id = 5;
				}
				$dlogp_conv->addItem('CONVERTING_FINISH_DATE', dlog::ITEM_TYPE_INFO, $elements[13], koi($v->finish_date_id));

				$v->notes = utf(trim($elements[14]));
				$dlogp_conv->addItem('CONVERTING_NOTES', dlog::ITEM_TYPE_INFO, $elements[14]);

			// checking validity of addr lenght (russian symbols)
				if(!is_valid($v->addr,5,5,'koi8-r'))
				{
					$ignored_lines++;
					$dlogp_conv->addItem('ADDR_NOT_VALID_SKIPPING', dlog::ITEM_TYPE_INFO, $v->addr);
					continue 2;
				}

			// creating item (one-line-value in final xml)
				$item = $estatedata_node->appendChild($xml->createElement('item'));

			// passing all values from $v to xml
				$item->setAttribute('addr',$v->addr);
				$item->setAttribute('addr_street_id',NULL);
				$item->setAttribute('addr_korp',NULL);
				$item->setAttribute('addr_house',NULL);
				$item->setAttribute('block_id',$v->block_id);
				$item->setAttribute('ceiling_height',NULL);
				$item->setAttribute('cost',$v->cost);
				$item->setAttribute('cost_meter',$v->cost_meter);
				$item->setAttribute('dekoration_type_id',NULL);
				$item->setAttribute('down_payment_cost',NULL);
				$item->setAttribute('down_payment_first_fee',NULL);
				$item->setAttribute('down_payment_month',NULL);
				$item->setAttribute('down_payment_year',NULL);
				$item->setAttribute('down_payment_cost_meter',NULL);
				$item->setAttribute('elevator_type_id',NULL);
				$item->setAttribute('finished',NULL);
				$item->setAttribute('finished_lived',NULL);
				$item->setAttribute('finish_date_id',$v->finish_date_id);
				$item->setAttribute('flat_type',NULL);
				$item->setAttribute('flats_merged',1);
				$item->setAttribute('house_type_id',$v->house_type_id);
				$item->setAttribute('house_section',NULL);
				$item->setAttribute('info_added_from','bsnrobot');
				$item->setAttribute('loggia_type_id',NULL);
				$item->setAttribute('notes',$v->notes);
				$item->setAttribute('object_id',NULL);
				$item->setAttribute('phone_exist',NULL);
				$item->setAttribute('rooms_count',$v->rooms_count);
				$item->setAttribute('seller_name',NULL);
				$item->setAttribute('seller_phone',$v->seller_phone);
				$item->setAttribute('sqear_full',$v->sqear_full);
				$item->setAttribute('sqear_rooms',NULL);
				$item->setAttribute('sqear_kitchen',$v->sqear_kitchen);
				$item->setAttribute('sqear_live',$v->sqear_live);
				$item->setAttribute('storey_start',$v->storey_start);
				$item->setAttribute('storey_end',NULL);
				$item->setAttribute('storey_total',$v->storey_total);
				$item->setAttribute('subway_id',$v->subway_id);
				$item->setAttribute('subway_length',$v->subway_length);
				$item->setAttribute('subway_length_method_id',$v->subway_length_method_id);
				$item->setAttribute('toilet_type_id',$v->toilet_type_id);
				$item->setAttribute('user_id',$user_id);

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
/* RESULTS:
in the end of all we have this variables:
$dparser->result ($xml) = final xml with parsed values
*/
?>