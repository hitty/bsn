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

$dparser->content = str_replace("\r\n", "\n", $dparser->content);
$bylines = explode("\n",$dparser->content);
$dlogp_bylines = $dlogp_process->addGroup('EXPLODING_BY_LINES');
$dlogp_bylines->addItem('LINES_COUNT', dlog::ITEM_TYPE_INFO, sizeof($bylines));

$dparser->result = new DOMDocument('1.0','koi8-r');
$xml =& $dparser->result;
$xml->formatOutput = true;

$root_node = $xml->appendChild($xml->createElement('root'));
$estatedata_node = $root_node->appendChild($xml->createElement('estatedata'));
$estatedata_node->setAttribute('type','live_rent');

$ignored_lines = 0;
$processed_total = 0;

$dlogp_lines = $dlogp_process->addGroup('PARSING_LINES');
foreach($bylines as $linenum => $oneline)
{
	//echo $oneline."($linenum)";die();

	$elements = explode(";",$oneline);
	$total_elements = sizeof($elements);

	//echo " total: $total_elements \n";
	
	$dlogp_line = $dlogp_lines->addGroup('PARSING_LINE');
	$dlogp_line->addItem('LINE_INFO', dlog::ITEM_TYPE_INFO, $linenum, $oneline, $total_elements);

	switch($total_elements):
		case 15:
		case 16:
			$mode = 'arend';
			$dlogp_line->addItem('PARSE_MODE', dlog::ITEM_TYPE_INFO, $mode);
			break;
		case 13: // возможно старый формат - район и улица через запятую а не отдельные поля
		default:
			$ignored_lines++;
			$dlogp_line->addItem('LINE_IGNORED', dlog::ITEM_TYPE_INFO);
			//echo $oneline."(line: $linenum, elements: $total_elements)\n";

			//trig_error(PARSER_PLAIN_BN_WRONG_PARAMETER_COUNT,$dparser->srcfilename,$linenum,$oneline);
			continue 2;
	endswitch;

	switch($mode):
		case 'arend':
			$dlogp_conv = $dlogp_line->addGroup('PROCESSING_ELEMENTS');
			
			if(preg_match('/ккв/',$elements[1]))
			{
				list($tmp_rooms_count) = sscanf($elements[1],'%dккв');
				$tmp_rooms_selling_count = NULL;
				$tmp_flat_type = 'F';
			}
			else if(preg_match('/комн/',$elements[1]))
			{
				$tmp_rooms_count = NULL;
				list($tmp_rooms_selling_count) = sscanf($elements[1],'%dкомн');
				$tmp_flat_type = 'R';
			}
			else
			{
				$ignored_lines++;
				continue 2;
			}
			$dlogp_conv->addItem('CONVERTING_FIELD_ROOMS_COUNT_AND_FLAT_TYPE', dlog::ITEM_TYPE_INFO, $elements[1], $tmp_rooms_count, $tmp_rooms_selling_count, $tmp_flat_type);
			
			list($tmp_storey,$tmp_storey_total) = plain_bn::grabStoreys($elements[4]);
			$dlogp_conv->addItem('CONVERTING_FIELD_STOREY_AND_STOREY_TOTAL', dlog::ITEM_TYPE_INFO, $elements[4], $tmp_storey, $tmp_storey_total);
			
			list($tmp_subway, $tmp_subway_length, $tmp_subway_length_method) = plain_bn::get_subway_id($elements[8]);
			$dlogp_conv->addItem('CONVERTING_FIELD_SUBWAY_AND_SUBWAY_LENGTH_AND_SUBWAY_LENGTH_METHOD', dlog::ITEM_TYPE_INFO, $elements[8], $tmp_subway, $tmp_subway_length, $tmp_subway_length_method);

			$tmp_cost_paid_type = ($elements[13] == 'С') ? 'DAY' : 'MONTH';
			$dlogp_conv->addItem('CONVERTING_FIELD_COST_PAID_TYPE', dlog::ITEM_TYPE_INFO, $elements[13], $tmp_cost_paid_type);

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
			$item->setAttribute('cost',utf($elements[12]));
			$item->setAttribute('cost_paid_type',$tmp_cost_paid_type);
			$item->setAttribute('elevator_type_id',NULL);
			$item->setAttribute('enter_type_id',NULL);
			$item->setAttribute('geyser_type_id',NULL);
			$item->setAttribute('house_type_id',NULL);
			$item->setAttribute('hotwater_type_id',NULL);
			$item->setAttribute('humans_live',NULL);
			$item->setAttribute('info_added_from','bsnrobot');
			$item->setAttribute('flat_type',$tmp_flat_type);
			$item->setAttribute('floor_type_id',NULL);
			$item->setAttribute('loggia_type_id',NULL);
			$item->setAttribute('notes',utf(trim($elements[14])));
			$item->setAttribute('phone_exist',utf(plain_bn::plusminus($elements[9])));
			$item->setAttribute('privatize_type_id',NULL);
			$item->setAttribute('quality_type_id',NULL);
			$item->setAttribute('rooms_count',utf($tmp_rooms_count));
			$item->setAttribute('rooms_selling_count',utf($tmp_rooms_selling_count));
			$item->setAttribute('seller_name',NULL);
			$item->setAttribute('seller_phone',utf($elements[11]));
			$item->setAttribute('storey',utf($tmp_storey));
			$item->setAttribute('storey_total',utf($tmp_storey_total));
			$item->setAttribute('storey_double',NULL);
			$item->setAttribute('subway_id',utf($tmp_subway));
			$item->setAttribute('subway_length',utf($tmp_subway_length));
			$item->setAttribute('subway_length_method_id',utf($tmp_subway_length_method));
			$item->setAttribute('sqear_full',utf(plain_bn::eval_sqear($elements[5])));
			$item->setAttribute('sqear_live',utf(plain_bn::eval_sqear($elements[6])));
			$item->setAttribute('sqear_kitchen',utf(plain_bn::eval_sqear($elements[7])));
			$item->setAttribute('sqear_rooms',NULL);
			$item->setAttribute('toilet_type_id',NULL);
			$item->setAttribute('user_id',$user_id);
			$item->setAttribute('window_type_id',NULL);

			$processed_total++;

			break;

	endswitch;
}

if($processed_total != 0)
{
	$user_messages->add('FILE_PARSED','NOTICE',$processed_total,$ignored_lines);
}
else
{
	$user_messages->add('FILE_PARSED_NO_DATA','WARNING');
}

$dlogp_process->addItem('PROCESSED_LINES', dlog::ITEM_TYPE_INFO, $processed_total);
$dlogp_process->addItem('IGNORED_LINES', dlog::ITEM_TYPE_INFO, $ignored_lines);

$dlogp->savelog($config['parser_parselog_file_prefix'],$config['parser_parselog_file_suffix'],$config['parser_parselog_file_path']);
?>
