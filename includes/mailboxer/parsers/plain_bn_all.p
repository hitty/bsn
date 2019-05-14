<?php
require_once('plain_bn_common.p');

// log variable
	$dlogp = new dlog('koi8-r','koi8-r');
	$dlogp_process = $dlogp->addGroup('RUNNING_PARSER');
	$dlogp_process->addItem('PARSER', dlog::ITEM_TYPE_INFO, 'plain_bn_ard.p');

// charset detection
	$charset = detect_cyr_charset($dparser->content, CHARSET_TEST_LIMIT);
	$dlogp_process->addItem('SOURCE_CHARSET', dlog::ITEM_TYPE_INFO, $charset);

// encoding received file (alt-koi => koi8-r)
	$dparser->content = @iconv($charset,'koi8-r',$dparser->content);
	$dlogp_process->addItem('CONVERTING_SOURCE', dlog::ITEM_TYPE_INFO);


$bylines = explode("\n",$dparser->content);
$dlogp_bylines = $dlogp_process->addGroup('EXPLODING_BY_LINES');
$dlogp_bylines->addItem('LINES_COUNT', dlog::ITEM_TYPE_INFO, sizeof($bylines));

$dparser->result = new DOMDocument('1.0','koi8-r');
$xml =& $dparser->result;
$xml->formatOutput = true;

$root_node = $xml->appendChild($xml->createElement('root'));
$estatedata_node = $root_node->appendChild($xml->createElement('estatedata'));
$estatedata_node->setAttribute('type','live_sell');


$processed_flats = 0;
$processed_rooms = 0;
$ignored_lines = 0;

$dlogp_lines = $dlogp_process->addGroup('PARSING_LINES');
foreach($bylines as $linenum => $oneline)
{
	$elements = explode(";",$oneline);
	$total_elements = sizeof($elements);

	$dlogp_line = $dlogp_lines->addGroup('PARSING_LINE');
	$dlogp_line->addItem('LINE_INFO', dlog::ITEM_TYPE_INFO, $linenum, $oneline, $total_elements);

	switch($total_elements):
		case 16:
		case 17:
		case 18:
			if(strstr($elements[1],'ËË×'))
			{
				$mode = 'flat';
			}
			else
			{
				$mode = 'room';
			}
			$dlogp_line->addItem('PARSE_MODE', dlog::ITEM_TYPE_INFO, $mode);
			break;
		case 1: // blank line?
		default:
			$ignored_lines++;
			$dlogp_line->addItem('LINE_IGNORED', dlog::ITEM_TYPE_INFO);
			//trig_error(PARSER_PLAIN_BN_WRONG_PARAMETER_COUNT,$dparser->srcfilename,$linenum,$oneline);
			continue 2;
	endswitch;

	switch($mode):
		case 'flat':
			$dlogp_conv = $dlogp_line->addGroup('PROCESSING_ELEMENTS');

			list($tmp_rooms_count) = sscanf($elements[1],'%dËË×');
			$dlogp_conv->addItem('CONVERTING_FIELD_ROOMS_COUNT', dlog::ITEM_TYPE_INFO, $elements[1], $tmp_rooms_count);

			list($tmp_storey,$tmp_storey_total) = plain_bn::grabStoreys($elements[4]);
			$dlogp_conv->addItem('CONVERTING_FIELD_STOREY_AND_STOREY_TOTAL', dlog::ITEM_TYPE_INFO, $elements[4], $tmp_storey, $tmp_storey_total);

			list($tmp_subway, $tmp_subway_length, $tmp_subway_length_method) = plain_bn::get_subway_id($elements[8]);
			$dlogp_conv->addItem('CONVERTING_FIELD_SUBWAY_AND_SUBWAY_LENGTH_AND_SUBWAY_LENGTH_METHOD', dlog::ITEM_TYPE_INFO, $elements[8], $tmp_subway, $tmp_subway_length, $tmp_subway_length_method);

			// checking validity of addr lenght (russian symbols)
				if(!is_valid($elements[3],5,5,'koi8-r'))
				{
					$ignored_lines++;
					$dlogp_conv->addItem('ADDR_NOT_VALID_SKIPPING', dlog::ITEM_TYPE_INFO, $elements[3]);
					continue 2;
				}
				$dlogp_conv->addItem('CONVERTING_FIELD_ADDR', dlog::ITEM_TYPE_INFO, $elements[3]);

			$item = $estatedata_node->appendChild($xml->createElement('item'));

			$item->setAttribute('addr',utf($elements[3]));
			$item->setAttribute('addr_street_id',NULL);
			$item->setAttribute('addr_house',NULL);
			$item->setAttribute('addr_korp',NULL);
			$item->setAttribute('block_id',utf(plain_bn::get_block_id($elements[2])));
			$item->setAttribute('ceiling_height',NULL);
			$item->setAttribute('confirm_status','Y');
			$item->setAttribute('cost',utf($elements[14] * 1000));
			$item->setAttribute('cost_type_paid',NULL);
			$item->setAttribute('elevator_type_id',NULL);
			$item->setAttribute('enter_type_id',NULL);
			$item->setAttribute('geyser_type_id',NULL);
			$item->setAttribute('house_type_id',utf(plain_bn::get_house_type_id($elements[10])));
			$item->setAttribute('hotwater_type_id',NULL);
			$item->setAttribute('humans_live',NULL);
			$item->setAttribute('info_added_from','bsnrobot');
			$item->setAttribute('flat_type','F');
			$item->setAttribute('floor_type_id',NULL);
			$item->setAttribute('loggia_type_id',NULL);
			$item->setAttribute('notes',utf(trim($elements[15])));
			$item->setAttribute('phone_exist',utf(plain_bn::plusminus($elements[9])));
			$item->setAttribute('privatize_type_id',NULL);
			$item->setAttribute('quality_type_id',NULL);
			$item->setAttribute('rooms_count',utf($tmp_rooms_count));
			$item->setAttribute('rooms_selling_count',NULL);
			$item->setAttribute('seller_name',utf($elements[12]));
			$item->setAttribute('seller_phone',utf($elements[13]));
			$item->setAttribute('storey',utf($tmp_storey));
			$item->setAttribute('storey_total',utf($tmp_storey_total));
			$item->setAttribute('storey_double',NULL);
			$item->setAttribute('subway_id',utf($tmp_subway));
			$item->setAttribute('subway_length',utf($tmp_subway_length));
			$item->setAttribute('subway_length_method_id',utf($tmp_subway_length_method));
			$item->setAttribute('sqear_full',utf(plain_bn::eval_sqear($elements[5])));
			$item->setAttribute('sqear_live',utf(plain_bn::eval_sqear($elements[6])));
			$item->setAttribute('sqear_kitchen',utf(plain_bn::eval_sqear($elements[7])));
			$item->setAttribute('sqear_rooms',utf($elements[6]));
			$item->setAttribute('toilet_type_id',utf(plain_bn::get_toilet_id($elements[11])));
			$item->setAttribute('user_id',$user_id);
			$item->setAttribute('window_type_id',NULL);

			$processed_flats++;

			break;

		case 'room':
			$dlogp_conv = $dlogp_line->addGroup('PROCESSING_ELEMENTS');

			list($tmp_storey,$tmp_storey_total) = plain_bn::grabStoreys($elements[5]);
			$dlogp_conv->addItem('CONVERTING_FIELD_STOREY_AND_STOREY_TOTAL', dlog::ITEM_TYPE_INFO, $elements[5], $tmp_storey, $tmp_storey_total);

			list($tmp_rooms_count,$tmp_humans_live) = plain_bn::get_rooms_count_humans_live($elements[8]);
			$dlogp_conv->addItem('CONVERTING_FIELD_ROOMS_COUNT_AND_HUMANS_LIVE', dlog::ITEM_TYPE_INFO, $elements[8], $tmp_rooms_count, $tmp_humans_live);

			list($tmp_subway,$tmp_subway_length, $tmp_subway_length_method) = plain_bn::get_subway_id($elements[9]);
			$dlogp_conv->addItem('CONVERTING_FIELD_SUBWAY_AND_SUBWAY_LENGTH_AND_SUBWAY_LENGTH_METHOD', dlog::ITEM_TYPE_INFO, $elements[9], $tmp_subway, $tmp_subway_length, $tmp_subway_length_method);

			// checking validity of addr lenght (russian symbols)
				if(!is_valid($elements[4],5,5,'koi8-r'))
				{
					$ignored_lines++;
					$dlogp_conv->addItem('ADDR_NOT_VALID_SKIPPING', dlog::ITEM_TYPE_INFO, $elements[4]);
					continue 2;
				}
				$dlogp_conv->addItem('CONVERTING_FIELD_ADDR', dlog::ITEM_TYPE_INFO, $elements[4]);

			$item = $estatedata_node->appendChild($xml->createElement('item'));

			$item->setAttribute('addr',utf($elements[4]));
			$item->setAttribute('addr_street_id',NULL);
			$item->setAttribute('addr_house',NULL);
			$item->setAttribute('addr_korp',NULL);
			$item->setAttribute('block_id',utf(plain_bn::get_block_id($elements[2])));
			$item->setAttribute('ceiling_height',NULL);
			$item->setAttribute('confirm_status','Y');
			$item->setAttribute('cost',utf($elements[15] * 1000));
			$item->setAttribute('cost_type_paid',NULL);
			$item->setAttribute('elevator_type_id',NULL);
			$item->setAttribute('enter_type_id',NULL);
			$item->setAttribute('geyser_type_id',NULL);
			$item->setAttribute('house_type_id',utf(plain_bn::get_house_type_id($elements[11])));
			$item->setAttribute('hotwater_type_id',NULL);
			$item->setAttribute('humans_live',utf($tmp_humans_live));
			$item->setAttribute('info_added_from','bsnrobot');
			$item->setAttribute('flat_type','R');
			$item->setAttribute('floor_type_id',NULL);
			$item->setAttribute('loggia_type_id',NULL);
			$item->setAttribute('notes',utf(trim($elements[16])));
			$item->setAttribute('phone_exist',utf(plain_bn::plusminus($elements[10])));
			$item->setAttribute('privatize_type_id',NULL);
			$item->setAttribute('quality_type_id',NULL);
			$item->setAttribute('rooms_count',utf($tmp_rooms_count));
			$item->setAttribute('rooms_selling_count',utf($elements[3]));
			$item->setAttribute('seller_name',utf($elements[13]));
			$item->setAttribute('seller_phone',utf($elements[14]));
			$item->setAttribute('storey',utf($tmp_storey));
			$item->setAttribute('storey_total',utf($tmp_storey_total));
			$item->setAttribute('storey_double',NULL);
			$item->setAttribute('subway_id',utf($tmp_subway));
			$item->setAttribute('subway_length',utf($tmp_subway_length));
			$item->setAttribute('subway_length_method_id',utf($tmp_subway_length_method));
			$item->setAttribute('sqear_full',NULL);
			$item->setAttribute('sqear_live',utf(plain_bn::eval_sqear($elements[6])));
			$item->setAttribute('sqear_kitchen',utf(plain_bn::eval_sqear($elements[7])));
			$item->setAttribute('sqear_rooms',utf($elements[6]));
			$item->setAttribute('toilet_type_id',utf(plain_bn::get_toilet_id($elements[12])));
			$item->setAttribute('user_id',$user_id);
			$item->setAttribute('window_type_id',NULL);

			$processed_rooms++;

			break;

	endswitch;
}

$processed_total = $processed_flats + $processed_rooms;

if($processed_total != 0)
{
	$user_messages->add('FILE_PARSED','NOTICE',$processed_total,$ignored_lines);
}
else
{
	$user_messages->add('FILE_PARSED_NO_DATA','WARNING');
}

$dlogp_process->addItem('PROCESSED_LINES', dlog::ITEM_TYPE_INFO, $processed_flats + $processed_rooms);
$dlogp_process->addItem('IGNORED_LINES', dlog::ITEM_TYPE_INFO, $ignored_lines);

$dlogp->savelog($config['parser_parselog_file_prefix'],$config['parser_parselog_file_suffix'],$config['parser_parselog_file_path']);
?>
