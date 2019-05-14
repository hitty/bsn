<?php
require_once('plain_bn_common.p');

// log variable
	$dlogp = new dlog('koi8-r','koi8-r');
	$dlogp_process = $dlogp->addGroup('RUNNING_PARSER');
	$dlogp_process->addItem('PARSER', dlog::ITEM_TYPE_INFO, 'plain_bn_zdd.p');

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
$estatedata_node->setAttribute('type','country_sell');

$processed_total = 0;
$ignored_lines = 0;

$dlogp_lines = $dlogp_process->addGroup('PARSING_LINES');
foreach($bylines as $linenum => $oneline)
{
	$elements = explode(";",$oneline);
	$total_elements = sizeof($elements);
	
	$dlogp_line = $dlogp_lines->addGroup('PARSING_LINE');
	$dlogp_line->addItem('LINE_INFO', dlog::ITEM_TYPE_INFO, $linenum, $oneline, $total_elements);

	switch ($elements[0]):
		case 'уч': // участки
			switch($total_elements):
				case 9:
				case 10:
					$mode = 'country-sell-uchastki';
					break;
				
				default:
					$ignored_lines++;
					$dlogp_line->addItem('LINE_IGNORED', dlog::ITEM_TYPE_INFO);
					continue 3;
			endswitch;
			break;
			
		case 'зд': // загородная
			switch($total_elements):
				case 16:
				case 17: // жирная строка
					$mode = 'country-sell';
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
	switch($mode):
		case 'country-sell-uchastki':
			
			$write_queued = true;
			
			$v->addr = utf($elements[2]);
			$dlogp_conv->addItem('CONVERTING_ADDR', dlog::ITEM_TYPE_INFO, $elements[2], koi($v->addr));
			
			$v->area_rights_type_id = utf(plain_bn::get_area_rights_type_id($elements[4]));
			$dlogp_conv->addItem('CONVERTING_AREA_RIGHTS', dlog::ITEM_TYPE_INFO, $elements[4], $v->area_rights_type_id);
			
			$v->block_country_type_id = utf(plain_bn::get_block_country_id($elements[1]));
			$dlogp_conv->addItem('CONVERTING_BLOCK_COUNTRY', dlog::ITEM_TYPE_INFO, $elements[1], $v->block_country_type_id);
			
			$v->cost = utf($elements[6] * 1000);
			$dlogp_conv->addItem('CONVERTING_COST', dlog::ITEM_TYPE_INFO, $elements[6], $v->cost);
			
			$v->drinking_water_type_id = NULL;
			$v->house_heating_type_id = NULL;
			$v->house_wall_fabric_type_id = NULL;
			$v->house_wiring_type_id = NULL;
			
			$v->object_type_id = 13; // участок
			$dlogp_conv->addItem('CONVERTING_OBJECT_TYPE', dlog::ITEM_TYPE_INFO, 13, $v->object_type_id);
			
			$v->notes = utf($elements[5]);
			$dlogp_conv->addItem('CONVERTING_NOTES', dlog::ITEM_TYPE_INFO, $elements[5]);
			
			$v->seller_name = trim(utf($elements[7]));
			$dlogp_conv->addItem('CONVERTING_SELLER_NAME', dlog::ITEM_TYPE_INFO, $elements[7], koi($v->seller_name));
			
			$v->seller_phone = trim(utf($elements[8]));
			$dlogp_conv->addItem('CONVERTING_SELLER_PHONE', dlog::ITEM_TYPE_INFO, $elements[8], koi($v->seller_phone));
			
			$v->sqear_area = utf(plain_bn::eval_sqear($elements[3]));
			$dlogp_conv->addItem('CONVERTING_SQUARE_AREA', dlog::ITEM_TYPE_INFO, $elements[3], koi($v->sqear_area));
			
			$v->sqear_full = NULL;
			$v->storey_total = NULL;
			break;

		case 'country-sell':

			$write_queued = true;
			
			$v->addr = utf($elements[3]);
			$dlogp_conv->addItem('CONVERTING_ADDR', dlog::ITEM_TYPE_INFO, $elements[3], koi($v->addr));
			
			$v->area_rights_type_id = utf(plain_bn::get_area_rights_type_id($elements[4]));
			$dlogp_conv->addItem('CONVERTING_AREA_RIGHTS', dlog::ITEM_TYPE_INFO, $elements[4], $v->area_rights_type_id);
			
			$v->block_country_type_id = utf(plain_bn::get_block_country_id($elements[1]));
			$dlogp_conv->addItem('CONVERTING_BLOCK_COUNTRY', dlog::ITEM_TYPE_INFO, $elements[1], $v->block_country_type_id);
			
			$v->cost = utf($elements[13] * 1000);
			$dlogp_conv->addItem('CONVERTING_COST', dlog::ITEM_TYPE_INFO, $elements[13], $v->cost);
			
			$v->drinking_water_type_id = utf(plain_bn::get_drinking_water_type_id($elements[11]));
			$dlogp_conv->addItem('CONVERTING_DRINKING_WATER', dlog::ITEM_TYPE_INFO, $elements[11], $v->drinking_water_type_id);
			
			$v->house_heating_type_id = utf(plain_bn::get_heating_type_id($elements[9]));
			$dlogp_conv->addItem('CONVERTING_HOUSE_HEATING', dlog::ITEM_TYPE_INFO, $elements[9], $v->house_heating_type_id);
			
			$v->house_wall_fabric_type_id = utf(plain_bn::get_house_wall_fabric_type_id($elements[8]));
			$dlogp_conv->addItem('CONVERTING_HOUSE_WALL_FABRIC', dlog::ITEM_TYPE_INFO, $elements[8], $v->house_wall_fabric_type_id);
			
			$v->house_wiring_type_id = utf(plain_bn::get_wiring_type_id($elements[10]));
			$dlogp_conv->addItem('CONVERTING_HOUSE_WIRING', dlog::ITEM_TYPE_INFO, $elements[10], $v->house_wiring_type_id);
			
			$v->object_type_id = utf(plain_bn::get_object_type_id($elements[2]));
			$dlogp_conv->addItem('CONVERTING_OBJECT_TYPE', dlog::ITEM_TYPE_INFO, $elements[2], $v->object_type_id);
			
			$v->notes = utf($elements[12]);
			$dlogp_conv->addItem('CONVERTING_NOTES', dlog::ITEM_TYPE_INFO, $elements[7]);
			
			$v->seller_name = trim(utf($elements[14]));
			$dlogp_conv->addItem('CONVERTING_SELLER_NAME', dlog::ITEM_TYPE_INFO, $elements[14], koi($v->seller_name));
			
			$v->seller_phone = trim(utf($elements[15]));
			$dlogp_conv->addItem('CONVERTING_SELLER_PHONE', dlog::ITEM_TYPE_INFO, $elements[15], koi($v->seller_phone));
			
			$v->sqear_area = utf(plain_bn::eval_sqear($elements[5]));
			$dlogp_conv->addItem('CONVERTING_SQUARE_AREA', dlog::ITEM_TYPE_INFO, $elements[5], koi($v->sqear_area));
			
			$v->sqear_full = utf(plain_bn::eval_sqear($elements[6]));
			$dlogp_conv->addItem('CONVERTING_SQUARE_FULL', dlog::ITEM_TYPE_INFO, $elements[6], koi($v->sqear_full));
			
			$v->storey_total = utf($elements[7]);
			$dlogp_conv->addItem('CONVERTING_STOREY_TOTAL', dlog::ITEM_TYPE_INFO, $elements[7], koi($v->storey_total));
			break; //}}}

	endswitch;

	if($write_queued === true)
	{
		// checking validity of addr lenght (russian symbols)
			if(!is_valid($v->addr,5,5,'koi8-r'))
			{
				$ignored_lines++;
				$dlogp_conv->addItem('ADDR_NOT_VALID_SKIPPING', dlog::ITEM_TYPE_INFO, $v->addr);
				continue;
			}

		$item = $estatedata_node->appendChild($xml->createElement('item'));

		$item->setAttribute('addr',$v->addr);
		$item->setAttribute('arend', 'N');
		$item->setAttribute('area_state_type_id',NULL);
		$item->setAttribute('area_rights_type_id',$v->area_rights_type_id);
		$item->setAttribute('block_country_id', $v->block_country_type_id);
		$item->setAttribute('build_year',NULL);
		$item->setAttribute('build_rights_type_id',NULL);
		$item->setAttribute('confirm_status', 'Y');
		$item->setAttribute('cost',$v->cost);
		$item->setAttribute('drinking_water_type_id', $v->drinking_water_type_id);
		$item->setAttribute('garage_type_id',NULL);
		$item->setAttribute('house_geyser_type_id',NULL);
		$item->setAttribute('house_wall_fabric_type_id',$v->house_wall_fabric_type_id);
		$item->setAttribute('house_roof_fabric_type_id',NULL);
		$item->setAttribute('house_heating_type_id',$v->house_heating_type_id);
		$item->setAttribute('house_wiring_type_id',$v->house_wiring_type_id);
		$item->setAttribute('house_bath_type_id',NULL);
		$item->setAttribute('info_added_from','bsnrobot');
		$item->setAttribute('notes',$v->notes);
		$item->setAttribute('object_type_id',$v->object_type_id);
		$item->setAttribute('phone_type_id',NULL);
		$item->setAttribute('pond_type_id',NULL);
		$item->setAttribute('purpose_type_id', NULL);
		$item->setAttribute('rooms_count',NULL);
		$item->setAttribute('residence_permit_type_id',NULL);
		$item->setAttribute('seller_name',$v->seller_name);
		$item->setAttribute('seller_phone',$v->seller_phone);
		$item->setAttribute('storey_total',$v->storey_total);
		$item->setAttribute('gd_station',NULL);
		$item->setAttribute('gd_station_length',NULL);
		$item->setAttribute('gd_station_length_method_id',NULL);
		$item->setAttribute('readiness_type_id',NULL);
		$item->setAttribute('sqear_full',$v->sqear_full);
		$item->setAttribute('sqear_live',NULL);
		$item->setAttribute('sqear_area',$v->sqear_area);
		$item->setAttribute('toilet_type_id',NULL);
		$item->setAttribute('user_id',$user_id);

		$processed_total++;
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