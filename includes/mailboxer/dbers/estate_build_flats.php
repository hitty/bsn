<?php
require_once('/home/bsnrobot/function_utf.php');
require_once('/home/bsnrobot/dbers/dber_checks.class.php');
require_once('/home/bsnrobot/dbers/dithered_sql.class.php');
require_once('/home/bsnrobot/dbers/function.grab_all_attributes.php');


function dber_estate_build_flats_remove($user_id, $remove_options)
{
	global $db;

	if($remove_options == REMOVE_ROBOT_ONLY)
	{
		$sql = "DELETE from flatdata.firsttrad where user_id = '" . $user_id . "' AND info_added_from = 'bsnrobot';";
	}
	else if($remove_options == REMOVE_ALL)
	{
		$sql = "DELETE from flatdata.firsttrad where user_id = '" . $user_id . "';";
	}


	if($db->querys($sql))
	{
		return $db->affected_rows;
	}
	else
	{
		return false;
	}
}
function dber_estate_build_flats($item_node)
{
	global $db, $dlog_adding;

	$dlog_var = $dlog_adding->addGroup('NEW VARIANT');

	$dlog_var->addItem('GRABBING PROPERTIES', dlog::ITEM_TYPE_INFO);
	$properties = grab_all_attributes($item_node);
	convert_array_utf_koi($properties);


	// address info
		list($addr,$addr_street_id,$addr_house,$addr_korp) = dber_checks::addr($properties['addr'],$properties['addr_street_id'],$properties['addr_house'],$properties['addr_korp']);

		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'addr', $properties['addr'], $addr);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'addr_street_id', $properties['addr_street_id'], $addr_street_id);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'addr_house', $properties['addr_house'], $addr_house);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'addr_korp', $properties['addr_korp'], $addr_korp);


		$dsql = new dithered_sql;

		$dsql->add_text_part('txt_addr',$addr);
		$dsql->add_text_part('house',$addr_house);
		$dsql->add_text_part('korp',$addr_korp);
		$dsql->add_text_part('id_addr',$addr_street_id);


	// user id
		$tmp = dber_checks::user_id($properties['user_id']);
		$dsql->add_text_part('user_id',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'user_id', $properties['user_id'], $tmp);


	// block id
		$tmp = dber_checks::db_relation($properties['block_id'],'flatdata.indblock');
		$dsql->add_text_part('id_block',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'block_id', $properties['block_id'], $tmp);


	// ceiling_height
		$tmp = dber_checks::float($properties['ceiling_height']);
		$dsql->add_text_part('height',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'ceiling_height', $properties['ceiling_height'], $tmp);


	// cost
		$tmp = dber_checks::float($properties['cost']);
		$dsql->add_text_part('cost',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'cost', $properties['cost'], $tmp);


	// cost_meter
		$tmp = dber_checks::float($properties['cost_meter']);
		$dsql->add_text_part('m_cost',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'cost_meter', $properties['cost_meter'], $tmp);


	// dekoration type id
		$tmp = dber_checks::db_relation($properties['dekoration_type_id'],'flatdata.inddekor');
		$dsql->add_text_part('id_dekor',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'dekoration_type_id', $properties['dekoration_type_id'], $tmp);


	// down_payment_cost
		$tmp = dber_checks::float($properties['down_payment_cost']);
		$dsql->add_text_part('r_cost',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'down_payment_cost', $properties['down_payment_cost'], $tmp);


	// down_payment_cost_meter
		$tmp = dber_checks::float($properties['down_payment_cost_meter']);
		$dsql->add_text_part('rr_cost',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'down_payment_cost_meter', $properties['down_payment_cost_meter'], $tmp);


	// down_payment_first_fee
		$tmp = dber_checks::float($properties['down_payment_first_fee']);
		$dsql->add_text_part('min_in',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'down_payment_first_fee', $properties['down_payment_first_fee'], $tmp);


	// down_payment_month
		$tmp = dber_checks::month($properties['down_payment_month']);
		$dsql->add_text_part('r_mon',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'down_payment_month', $properties['down_payment_month'], $tmp);


	// down_payment_year
		$tmp = dber_checks::year($properties['down_payment_year']);
		$dsql->add_text_part('r_year',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'down_payment_year', $properties['down_payment_year'], $tmp);


	// elevator type id
		$tmp = dber_checks::db_relation($properties['elevator_type_id'],'flatdata.indelev');
		$dsql->add_text_part('id_elev',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'elevator_type_id', $properties['elevator_type_id'], $tmp);


	// finish date id
		$tmp = dber_checks::db_relation($properties['finish_date_id'],'flatdata.indfinw');
		$dsql->add_text_part('id_finw',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'finish_date_id', $properties['finish_date_id'], $tmp);


	// finished
		$tmp = dber_checks::yn($properties['finished']);
		$dsql->add_text_part('fin',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'finished', $properties['finished'], $tmp);


	// finished_lived
		$tmp = dber_checks::yn($properties['finished_lived']);
		$dsql->add_text_part('finliv',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'finished_lived', $properties['finished_lived'], $tmp);


	// flat_type
		$tmp = dber_checks::flat_type_build($properties['flat_type']);
		$dsql->add_text_part('t_kvart',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'flat_type', $properties['flat_type'], $tmp);


	// flats merged
		$tmp = dber_checks::integer($properties['flats_merged']);
		$dsql->add_text_part('flats',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'flats_merged', $properties['flats_merged'], $tmp);


	// house type id
		$tmp = dber_checks::db_relation($properties['house_type_id'],'flatdata.indhtyp');
		$dsql->add_text_part('id_htype',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'house_type_id', $properties['house_type_id'], $tmp);


	// house_section
		$tmp = dber_checks::house_section($properties['house_section']);
		$dsql->add_text_part('section',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'house_section', $properties['house_section'], $tmp);


	// !! info added from
		$tmp = dber_checks::info_added_from($properties['info_added_from']);
		$dsql->add_text_part('ofrom','es');
		$dsql->add_text_part('info_added_from',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'info_added_from', $properties['info_added_from'], $tmp);


	// loggia type id
		$tmp = dber_checks::db_relation($properties['loggia_type_id'],'flatdata.indlod');
		$dsql->add_text_part('id_lod',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'loggia_type_id', $properties['loggia_type_id'], $tmp);


	// notes
		$tmp = dber_checks::notes($properties['notes']);
		$dsql->add_text_part('notes',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'notes', $properties['notes'], $tmp);


	// phone_exist
		$tmp = dber_checks::yn($properties['phone_exist']);
		$dsql->add_text_part('phone',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'phone_exist', $properties['phone_exist'], $tmp);


	// rooms count
		$tmp = dber_checks::integer($properties['rooms_count']);
		$dsql->add_text_part('rooms',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'rooms_count', $properties['rooms_count'], $tmp);


	// seller name
		$tmp = dber_checks::seller_name($properties['seller_name']);
		$dsql->add_text_part('a_name',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'seller_name', $properties['seller_name'], $tmp);


	// seller phone
		$tmp = dber_checks::phone_number($properties['seller_phone']);
		$dsql->add_text_part('a_tel',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'seller_phone', $properties['seller_phone'], $tmp);


	// sqears
		$tmp = dber_checks::float($properties['sqear_full']);
		$dsql->add_text_part('fsqear',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'sqear_full', $properties['sqear_full'], $tmp);

		$tmp = dber_checks::float($properties['sqear_live']);
		$dsql->add_text_part('lsqear',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'sqear_live', $properties['sqear_live'], $tmp);

		$tmp = dber_checks::float($properties['sqear_kitchen']);
		$dsql->add_text_part('ksqear',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'sqear_kitchen', $properties['sqear_kitchen'], $tmp);

		$tmp = dber_checks::float_extended($properties['sqear_rooms']);
		$dsql->add_text_part('rsqear',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'sqear_rooms', $properties['sqear_rooms'], $tmp);


	// storeys
		$tmp = dber_checks::integer($properties['storey_start']);
		$dsql->add_text_part('floor',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'storey_start', $properties['storey_start'], $tmp);

		$tmp = dber_checks::integer($properties['storey_end']);
		$dsql->add_text_part('p_floor',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'storey_end', $properties['storey_end'], $tmp);

		$tmp = dber_checks::integer($properties['storey_total']);
		$dsql->add_text_part('c_floor',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'storey_total', $properties['storey_total'], $tmp);


	// subway id
		$tmp = dber_checks::db_relation($properties['subway_id'],'flatdata.indsubw');
		$dsql->add_text_part('id_subw',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'subway_id', $properties['subway_id'], $tmp);

	// subway length
		$tmp = dber_checks::float($properties['subway_length']);
		$dsql->add_text_part('len',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'subway_length', $properties['subway_length'], $tmp);

		$tmp = dber_checks::db_relation($properties['subway_length_method_id'],'flatdata.indlen');
		$dsql->add_text_part('id_len',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'subway_length_method_id', $properties['subway_length_method_id'], $tmp);


	// toilet type id
		$tmp = dber_checks::db_relation($properties['toilet_type_id'],'flatdata.indtoil');
		$dsql->add_text_part('id_toil',$tmp);
		$dlog_var->addItem('ATTRIBUTE_CONVERTED', dlog::ITEM_TYPE_INFO, 'toilet_type_id', $properties['toilet_type_id'], $tmp);


	// some other
		$dsql->add_mysql_part('gid_obj','NULL');
		$dsql->add_mysql_part('idate','now()');
		$dsql->add_mysql_part('itime','now()');


	return "INSERT DELAYED INTO `flatdata`.`firsttrad` SET " . $dsql->get_complete_string();
}

?>
