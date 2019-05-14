<?php
require_once('plain_bn_common.p');

// log variable
	$dlogp = new dlog('koi8-r','koi8-r');
	$dlogp_process = $dlogp->addGroup('RUNNING_PARSER');
	$dlogp_process->addItem('PARSER', dlog::ITEM_TYPE_INFO, 'plain_bn_kn.p');

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
$estatedata_node->setAttribute('type','commercial');


$processed_total = 0;
$ignored_lines = 0;

//echo $dparser->content;
//die();

$dlogp_lines = $dlogp_process->addGroup('PARSING_LINES');
foreach($bylines as $linenum => $oneline)
{
	$elements = explode(";",$oneline);
	$total_elements = sizeof($elements);

	$dlogp_line = $dlogp_lines->addGroup('PARSING_LINE');
	$dlogp_line->addItem('LINE_INFO', dlog::ITEM_TYPE_INFO, $linenum, $oneline, $total_elements);

	//print_r($elements);
	switch($elements[0]):
		case 'ко':
			switch($total_elements):
				case 11:
				case 12: // жирная строка
					$mode = 'commercial-sell-offices';
					break;
				default:
					$ignored_lines++;
					$dlogp_line->addItem('LINE_IGNORED', dlog::ITEM_TYPE_INFO);
					continue 3;
			endswitch;
			break;
		case 'КО':
			switch($total_elements):
				case 14:
				case 15: // жирная строка
					$mode = 'commercial-rent-offices';
					break;
				default:
					$ignored_lines++;
					$dlogp_line->addItem('LINE_IGNORED', dlog::ITEM_TYPE_INFO);
					continue 3;
			endswitch;
			break;
		case 'км':
			switch($total_elements):
				case 11:
				case 12: // жирная строка
					$mode = 'commercial-sell-uslugi';
					break;
				default:
					$ignored_lines++;
					$dlogp_line->addItem('LINE_IGNORED', dlog::ITEM_TYPE_INFO);
					continue 3;
			endswitch;
			break;
		case 'КМ':
			switch($total_elements):
				case 11:
				case 12: // жирная строка
					$mode = 'commercial-rent-uslugi';
					break;
				default:
					$ignored_lines++;
					$dlogp_line->addItem('LINE_IGNORED', dlog::ITEM_TYPE_INFO);
					continue 3;
			endswitch;
			break;
		case 'кр':
			switch($total_elements):
				case 11:
				case 12: // жирная строка
					$mode = 'commercial-sell-rundom';
					break;
				default:
					$ignored_lines++;
					$dlogp_line->addItem('LINE_IGNORED', dlog::ITEM_TYPE_INFO);
					continue 3;
			endswitch;
			break;
		case 'КР':
			switch($total_elements):
				case 11:
				case 12: // жирная строка
					$mode = 'commercial-rent-rundom';
					break;
				default:
					$ignored_lines++;
					$dlogp_line->addItem('LINE_IGNORED', dlog::ITEM_TYPE_INFO);
					continue 3;
			endswitch;
			break;
		case 'кс':
			switch($total_elements):
				case 16:
				case 17: // жирная строка
					$mode = 'commercial-sell-sclad';
					break;
				default:
					$ignored_lines++;
					$dlogp_line->addItem('LINE_IGNORED', dlog::ITEM_TYPE_INFO);
					continue 3;
			endswitch;
			break;
		case 'КС':
			switch($total_elements):
				case 15:
				case 16: // жирная строка
					$mode = 'commercial-rent-sclad';
					break;
				default:
					$ignored_lines++;
					$dlogp_line->addItem('LINE_IGNORED', dlog::ITEM_TYPE_INFO);
					continue 3;
			endswitch;
			break;
		case 'кз':
			switch($total_elements):
				case 12:
				case 13: // жирная строка
					$mode = 'commercial-sell-buildings';
					break;
				default:
					$ignored_lines++;
					$dlogp_line->addItem('LINE_IGNORED', dlog::ITEM_TYPE_INFO);
					continue 3;
			endswitch;
			break;
		case 'КЗ':
			switch($total_elements):
				case 11:
				case 12: // жирная строка
					$mode = 'commercial_rent_buildings';
					break;
				default:
					$ignored_lines++;
					$dlogp_line->addItem('LINE_IGNORED', dlog::ITEM_TYPE_INFO);
					continue 3;
			endswitch;
			break;
		case 'ку':
			switch($total_elements):
				case 9:
				case 10: // жирная строка
					$mode = 'commercial-sell-uchastki';
					break;
				default:
					$ignored_lines++;
					$dlogp_line->addItem('LINE_IGNORED', dlog::ITEM_TYPE_INFO);
					continue 3;
			endswitch;
			break;
		default:
			$ignored_lines++;
			$dlogp_line->addItem('LINE_IGNORED', dlog::ITEM_TYPE_INFO);
			continue 2;
	endswitch;
	$dlogp_line->addItem('PARSE_MODE', dlog::ITEM_TYPE_INFO, $mode);


	$write_queued = false;

	$dlogp_conv = $dlogp_line->addGroup('PROCESSING_ELEMENTS');

	$v = new stdClass;

	$v->block_id = NULL;
	$v->addr = NULL;
	$v->sqear_full_start = NULL;
	$v->storey_start = NULL;
	$v->storey_total = NULL;
	$v->enter_type_id = NULL;
	$v->notes = NULL;
	$v->cost = NULL;
	$v->seller_phone = NULL;
	$v->sqear_area = NULL;
	$v->sqear_area_type_id = NULL;
	$v->purpose_type_id = NULL;
	$v->phone_lines_count = NULL;
	$v->deal_type_id = NULL;

	switch($mode):
		case 'commercial-sell-offices':
			$v->purpose_type_id = 6;
			$v->deal_type_id = is_null($v->deal_type_id) ? 2 : $v->deal_type_id;
		case 'commercial-sell-uslugi':
			$v->deal_type_id = is_null($v->deal_type_id) ? 2 : $v->deal_type_id;
		case 'commercial-rent-uslugi':
			$v->purpose_type_id = is_null($v->purpose_type_id) ? 15 : $v->purpose_type_id;
			$v->deal_type_id = is_null($v->deal_type_id) ? 3 : $v->deal_type_id;
		case 'commercial-sell-rundom':
			$v->deal_type_id = is_null($v->deal_type_id) ? 2 : $v->deal_type_id;
		case 'commercial-rent-rundom':
			$v->deal_type_id = is_null($v->deal_type_id) ? 3 : $v->deal_type_id;

			$v->purpose_type_id = is_null($v->purpose_type_id) ? 14 : $v->purpose_type_id;

			$dlogp_conv->addItem('SETTING_PURPOSE_TYPE_ID_AND_DEAL_TYPE_ID', dlog::ITEM_TYPE_INFO, $v->purpose_type_id, $v->deal_type_id);

			$processed_total++;
			$write_queued = true;

			$v->block_id = utf(plain_bn::get_block_id($elements[1]));
			$dlogp_conv->addItem('CONVERTING_BLOCK', dlog::ITEM_TYPE_INFO, $elements[1], $v->block_id);

			$v->addr = utf($elements[2]);
			$dlogp_conv->addItem('CONVERTING_ADDR', dlog::ITEM_TYPE_INFO, $elements[2], koi($v->addr));

			$v->sqear_full_start = utf(plain_bn::eval_sqear($elements[3]));
			$dlogp_conv->addItem('CONVERTING_SQUARE_FULL_START', dlog::ITEM_TYPE_INFO, $elements[3], koi($v->sqear_full_start));

			$storeys = plain_bn::grabStoreys($elements[4]);
			$v->storey_start = utf($storeys[0]);
			$v->storey_total = utf($storeys[1]);
			$dlogp_conv->addItem('CONVERTING_STOREY_START_AND_STOREY_TOTAL', dlog::ITEM_TYPE_INFO, $elements[4], koi($v->storey_start), koi($v->storey_total));

			$v->enter_type_id = utf(plain_bn::get_enter_type_id($elements[5]));
			$dlogp_conv->addItem('CONVERTING_ENTER_TYPE', dlog::ITEM_TYPE_INFO, $elements[5], koi($v->enter_type_id));

			$v->notes = utf($elements[7]);
			$dlogp_conv->addItem('CONVERTING_NOTES', dlog::ITEM_TYPE_INFO, $elements[7]);

			$v->cost = utf($elements[8] * 1000);
			$dlogp_conv->addItem('CONVERTING_COST', dlog::ITEM_TYPE_INFO, $elements[8], koi($v->cost));

			$v->seller_phone = utf(trim($elements[10]));
			$dlogp_conv->addItem('CONVERTING_SELLER_PHONE', dlog::ITEM_TYPE_INFO, $elements[10], koi($v->seller_phone));


			break;

		case 'commercial-sell-sclad':
			$processed_total++;
			$write_queued = true;

			$v->purpose_type_id = 17;
			$v->deal_type_id = 2;
			$dlogp_conv->addItem('SETTING_PURPOSE_TYPE_ID_AND_DEAL_TYPE_ID', dlog::ITEM_TYPE_INFO, $v->purpose_type_id, $v->deal_type_id);

			$v->block_id = utf(plain_bn::get_block_id($elements[1]));
			$dlogp_conv->addItem('CONVERTING_BLOCK', dlog::ITEM_TYPE_INFO, $elements[1], $v->block_id);

			$v->addr = utf($elements[2]);
			$dlogp_conv->addItem('CONVERTING_ADDR', dlog::ITEM_TYPE_INFO, $elements[2], koi($v->addr));

			$v->sqear_full_start = utf(plain_bn::eval_sqear($elements[3]));
			$dlogp_conv->addItem('CONVERTING_SQUARE_FULL_START', dlog::ITEM_TYPE_INFO, $elements[3], koi($v->sqear_full_start));

			$v->sqear_area = utf(plain_bn::eval_sqear($elements[11]));
			$v->sqear_area_type_id = utf(plain_bn::get_sqear_area_type($elements[11]));
			$dlogp_conv->addItem('CONVERTING_SQUARE_AREA_AND_SQUARE_AREA_TYPE', dlog::ITEM_TYPE_INFO, $elements[11], koi($v->sqear_area), koi($v->sqear_area_type_id));

			$v->notes = utf($elements[12]);
			$dlogp_conv->addItem('CONVERTING_NOTES', dlog::ITEM_TYPE_INFO, $elements[12]);

			$v->cost = utf($elements[13] * 1000);
			$dlogp_conv->addItem('CONVERTING_COST', dlog::ITEM_TYPE_INFO, $elements[13], koi($v->cost));

			$v->seller_phone = utf(trim($elements[15]));
			$dlogp_conv->addItem('CONVERTING_SELLER_PHONE', dlog::ITEM_TYPE_INFO, $elements[15], koi($v->seller_phone));
			break;

		case 'commercial-rent-sclad':
			$processed_total++;
			$write_queued = true;

			$v->purpose_type_id = 17;
			$v->deal_type_id = 3;
			$dlogp_conv->addItem('SETTING_PURPOSE_TYPE_ID_AND_DEAL_TYPE_ID', dlog::ITEM_TYPE_INFO, $v->purpose_type_id, $v->deal_type_id);

			$v->block_id = utf(plain_bn::get_block_id($elements[1]));
			$dlogp_conv->addItem('CONVERTING_BLOCK', dlog::ITEM_TYPE_INFO, $elements[1], $v->block_id);

			$v->addr = utf($elements[2]);
			$dlogp_conv->addItem('CONVERTING_ADDR', dlog::ITEM_TYPE_INFO, $elements[2], koi($v->addr));

			$v->sqear_full_start = utf(plain_bn::eval_sqear($elements[3]));
			$dlogp_conv->addItem('CONVERTING_SQUARE_FULL_START', dlog::ITEM_TYPE_INFO, $elements[3], koi($v->sqear_full_start));

			$v->notes = utf($elements[11]);
			$dlogp_conv->addItem('CONVERTING_NOTES', dlog::ITEM_TYPE_INFO, $elements[11]);

			$v->cost = utf($elements[12]);
			$dlogp_conv->addItem('CONVERTING_COST', dlog::ITEM_TYPE_INFO, $elements[12], koi($v->cost));

			$v->seller_phone = utf(trim($elements[14]));
			$dlogp_conv->addItem('CONVERTING_SELLER_PHONE', dlog::ITEM_TYPE_INFO, $elements[14], koi($v->seller_phone));
			break;

		case 'commercial-sell-buildings':
			$processed_total++;
			$write_queued = true;

			$v->purpose_type_id = 14;
			$v->deal_type_id = 2;
			$dlogp_conv->addItem('SETTING_PURPOSE_TYPE_ID_AND_DEAL_TYPE_ID', dlog::ITEM_TYPE_INFO, $v->purpose_type_id, $v->deal_type_id);

			$v->block_id = utf(plain_bn::get_block_id($elements[1]));
			$dlogp_conv->addItem('CONVERTING_BLOCK', dlog::ITEM_TYPE_INFO, $elements[1], $v->block_id);

			$v->addr = utf($elements[2]);
			$dlogp_conv->addItem('CONVERTING_ADDR', dlog::ITEM_TYPE_INFO, $elements[2], koi($v->addr));

			$v->sqear_full_start = utf(plain_bn::eval_sqear($elements[3]));
			$dlogp_conv->addItem('CONVERTING_SQUARE_FULL_START', dlog::ITEM_TYPE_INFO, $elements[3], koi($v->sqear_full_start));

			$storeys = plain_bn::grabStoreys($elements[4]);
			$v->storey_start = utf($storeys[0]);
			$v->storey_total = utf($storeys[1]);
			$dlogp_conv->addItem('CONVERTING_STOREY_START_AND_STOREY_TOTAL', dlog::ITEM_TYPE_INFO, $elements[4], koi($v->storey_start), koi($v->storey_total));

			$v->sqear_area = utf(plain_bn::eval_sqear($elements[7]));
			$v->sqear_area_type_id = utf(plain_bn::get_sqear_area_type($elements[7]));
			$dlogp_conv->addItem('CONVERTING_SQUARE_AREA_AND_SQUARE_AREA_TYPE', dlog::ITEM_TYPE_INFO, $elements[7], koi($v->sqear_area), koi($v->sqear_area_type_id));

			$v->notes = utf($elements[8]);
			$dlogp_conv->addItem('CONVERTING_NOTES', dlog::ITEM_TYPE_INFO, $elements[8]);

			$v->cost = utf($elements[9] * 1000);
			$dlogp_conv->addItem('CONVERTING_COST', dlog::ITEM_TYPE_INFO, $elements[9], koi($v->cost));

			$v->seller_phone = utf(trim($elements[11]));
			$dlogp_conv->addItem('CONVERTING_SELLER_PHONE', dlog::ITEM_TYPE_INFO, $elements[11], koi($v->seller_phone));
			break;

		case 'commercial-rent-buildings':
			$processed_total++;
			$write_queued = true;

			$v->purpose_type_id = 14;
			$v->deal_type_id = 3;
			$dlogp_conv->addItem('SETTING_PURPOSE_TYPE_ID_AND_DEAL_TYPE_ID', dlog::ITEM_TYPE_INFO, $v->purpose_type_id, $v->deal_type_id);

			$v->block_id = utf(plain_bn::get_block_id($elements[1]));
			$dlogp_conv->addItem('CONVERTING_BLOCK', dlog::ITEM_TYPE_INFO, $elements[1], $v->block_id);

			$v->addr = utf($elements[2]);
			$dlogp_conv->addItem('CONVERTING_ADDR', dlog::ITEM_TYPE_INFO, $elements[2], koi($v->addr));

			$v->sqear_full_start = utf(plain_bn::eval_sqear($elements[3]));
			$dlogp_conv->addItem('CONVERTING_SQUARE_FULL_START', dlog::ITEM_TYPE_INFO, $elements[3], koi($v->sqear_full_start));

			$storeys = plain_bn::grabStoreys($elements[4]);
			$v->storey_start = utf($storeys[0]);
			$v->storey_total = utf($storeys[1]);
			$dlogp_conv->addItem('CONVERTING_STOREY_START_AND_STOREY_TOTAL', dlog::ITEM_TYPE_INFO, $elements[4], koi($v->storey_start), koi($v->storey_total));

			$v->notes = utf($elements[7]);
			$dlogp_conv->addItem('CONVERTING_NOTES', dlog::ITEM_TYPE_INFO, $elements[8]);

			$v->cost = utf($elements[8]);
			$dlogp_conv->addItem('CONVERTING_COST', dlog::ITEM_TYPE_INFO, $elements[8], koi($v->cost));

			$v->seller_phone = utf(trim($elements[10]));
			$dlogp_conv->addItem('CONVERTING_SELLER_PHONE', dlog::ITEM_TYPE_INFO, $elements[10], koi($v->seller_phone));
			break;

		case 'commercial-sell-uchastki':
			$processed_total++;
			$write_queued = true;

			$v->purpose_type_id = 14;
			$v->deal_type_id = 2;
			$dlogp_conv->addItem('SETTING_PURPOSE_TYPE_ID_AND_DEAL_TYPE_ID', dlog::ITEM_TYPE_INFO, $v->purpose_type_id, $v->deal_type_id);

			$v->block_id = utf(plain_bn::get_block_id($elements[1]));
			$dlogp_conv->addItem('CONVERTING_BLOCK', dlog::ITEM_TYPE_INFO, $elements[1], $v->block_id);

			$v->addr = utf($elements[2]);
			$dlogp_conv->addItem('CONVERTING_ADDR', dlog::ITEM_TYPE_INFO, $elements[2], koi($v->addr));

			$v->sqear_area = utf(plain_bn::eval_sqear($elements[3]));
			$v->sqear_area_type_id = utf(plain_bn::get_sqear_area_type($elements[3]));
			$dlogp_conv->addItem('CONVERTING_SQUARE_AREA_AND_SQUARE_AREA_TYPE', dlog::ITEM_TYPE_INFO, $elements[3], koi($v->sqear_area), koi($v->sqear_area_type_id));

			$v->notes = utf($elements[6]);
			$dlogp_conv->addItem('CONVERTING_NOTES', dlog::ITEM_TYPE_INFO, $elements[6]);

			$v->cost = utf($elements[7] * 1000);
			$dlogp_conv->addItem('CONVERTING_COST', dlog::ITEM_TYPE_INFO, $elements[7], koi($v->cost));

			$v->seller_phone = utf(trim($elements[8]));
			$dlogp_conv->addItem('CONVERTING_SELLER_PHONE', dlog::ITEM_TYPE_INFO, $elements[8], koi($v->seller_phone));
			break;

		case 'commercial-rent-offices':
			$processed_total++;
			$write_queued = true;

			$v->deal_type_id = 3;
			$dlogp_conv->addItem('SETTING_PURPOSE_TYPE_ID_AND_DEAL_TYPE_ID', dlog::ITEM_TYPE_INFO, $v->purpose_type_id, $v->deal_type_id);

			$v->block_id = utf(plain_bn::get_block_id($elements[1]));
			$dlogp_conv->addItem('CONVERTING_BLOCK', dlog::ITEM_TYPE_INFO, $elements[1], $v->block_id);

			$v->addr = utf($elements[2]);
			$dlogp_conv->addItem('CONVERTING_ADDR', dlog::ITEM_TYPE_INFO, $elements[2], koi($v->addr));

			$v->sqear_full_start = utf(plain_bn::eval_sqear($elements[3]));
			$dlogp_conv->addItem('CONVERTING_SQUARE_FULL_START', dlog::ITEM_TYPE_INFO, $elements[3], koi($v->sqear_full_start));

			$storeys = plain_bn::grabStoreys($elements[4]);
			$v->storey_start = utf($storeys[0]);
			$v->storey_total = utf($storeys[1]);
			$dlogp_conv->addItem('CONVERTING_STOREY_START_AND_STOREY_TOTAL', dlog::ITEM_TYPE_INFO, $elements[4], koi($v->storey_start), koi($v->storey_total));

			$v->phone_lines_count = utf($elements[7]);
			$dlogp_conv->addItem('CONVERTING_PHONE_LINES_COUNT', dlog::ITEM_TYPE_INFO, $elements[7], koi($v->phone_lines_count));

			$v->notes = utf($elements[10]);
			$dlogp_conv->addItem('CONVERTING_NOTES', dlog::ITEM_TYPE_INFO, $elements[10]);

			$v->cost = utf($elements[11]);
			$dlogp_conv->addItem('CONVERTING_COST', dlog::ITEM_TYPE_INFO, $elements[11], koi($v->cost));

			$v->seller_phone = utf(trim($elements[13]));
			$dlogp_conv->addItem('CONVERTING_SELLER_PHONE', dlog::ITEM_TYPE_INFO, $elements[13], koi($v->seller_phone));
			break;


			//{{{

	endswitch;

	if($write_queued === true)
	{
		// checking validity of addr lenght (russian symbols)
			if(!is_valid($v->addr,5,5,'koi8-r'))
			{
				$processed_total--;
				$ignored_lines++;
				$dlogp_conv->addItem('ADDR_NOT_VALID_SKIPPING', dlog::ITEM_TYPE_INFO, $v->addr);
				continue;
			}

		$item = $estatedata_node->appendChild($xml->createElement('item'));

		$item->setAttribute('addr',$v->addr);
		$item->setAttribute('addr_street_id',NULL);
		$item->setAttribute('addr_house',NULL);
		$item->setAttribute('addr_korp',NULL);
		$item->setAttribute('block_id',$v->block_id);
		$item->setAttribute('building_type_id',NULL);
		$item->setAttribute('confirm_status','Y');
		$item->setAttribute('cost',$v->cost);
		$item->setAttribute('deal_type_id',$v->deal_type_id);
		$item->setAttribute('enter_type_id',$v->enter_type_id);
		$item->setAttribute('estate_rights_type_id',NULL);
		$item->setAttribute('house_type_id',NULL);
		$item->setAttribute('info_added_from','bsnrobot');
		$item->setAttribute('notes',$v->notes);
		$item->setAttribute('object_type_id',NULL);
		$item->setAttribute('phone_lines_count',NULL);
		$item->setAttribute('purpose_type_id',$v->purpose_type_id);
		$item->setAttribute('rooms_count',NULL);
		$item->setAttribute('seller_name',NULL);
		$item->setAttribute('seller_phone',$v->seller_phone);
		$item->setAttribute('storey_start',$v->storey_start);
		$item->setAttribute('storey_end',NULL);
		$item->setAttribute('storey_total',$v->storey_total);
		$item->setAttribute('subway_id',NULL);
		$item->setAttribute('subway_length',NULL);
		$item->setAttribute('subway_length_method_id',NULL);
		$item->setAttribute('sqear_full_start',$v->sqear_full_start);
		$item->setAttribute('sqear_full_end',NULL);
		$item->setAttribute('sqear_usefull',NULL);
		$item->setAttribute('sqear_building',NULL);
		$item->setAttribute('sqear_building_type_id',NULL);
		$item->setAttribute('sqear_area',$v->sqear_area);
		$item->setAttribute('sqear_area_type_id',$v->sqear_area_type_id);
		$item->setAttribute('user_id',$user_id);
	}
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