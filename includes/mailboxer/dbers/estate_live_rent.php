<?php
require_once('/home/bsnrobot/function_utf.php');
require_once('/home/bsnrobot/dbers/dber_checks.class.php');
require_once('/home/bsnrobot/dbers/dithered_sql.class.php');
require_once('/home/bsnrobot/dbers/function.grab_all_attributes.php');

function dber_estate_live_rent_remove($user_id, $delete_options)
{
	global $db;

	if($delete_options == REMOVE_ROBOT_ONLY)
	{
		$sql = "DELETE from flatdata.livtrad where user_id = '" . $user_id . "' AND arend = 'Y' AND info_added_from = 'bsnrobot';";
	}
	else if($delete_options == REMOVE_ALL)
	{
		$sql = "DELETE from flatdata.livtrad where user_id = '" . $user_id . "' AND arend = 'Y';";
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

function dber_estate_live_rent($item_node)
{
	global $db;

	$properties = grab_all_attributes($item_node);
	convert_array_utf_koi($properties);


	// address info
		list($addr,$addr_street_id,$addr_house,$addr_korp) = dber_checks::addr($properties['addr'],$properties['addr_street_id'],$properties['addr_house'],$properties['addr_korp']);


		$dsql = new dithered_sql;

		$dsql->add_text_part('txt_addr',$addr);
		$dsql->add_text_part('house',$addr_house);
		$dsql->add_text_part('korp',$addr_korp);
		$dsql->add_text_part('id_addr',$addr_street_id);


	// user id
		$tmp = dber_checks::user_id($properties['user_id']);
		$dsql->add_text_part('user_id',$tmp);


	// block id
		$tmp = dber_checks::db_relation($properties['block_id'],'flatdata.indblock');
		$dsql->add_text_part('id_block',$tmp);


	// ceiling_height
		$tmp = dber_checks::float($properties['ceiling_height']);
		$dsql->add_text_part('height',$tmp);

	// confirmation status
		$tmp = dber_checks::yn($properties['confirm_status']);
		$dsql->add_text_part('sel_allow',$tmp);
		$dsql->add_text_part('actual',$tmp);

	// cost
		$tmp = dber_checks::float($properties['cost']);
		$dsql->add_text_part('cost',$tmp);
		$dsql->add_text_part('a_cost_b',$tmp);
		$dsql->add_text_part('a_cost_e',$tmp);

	// cost type paid (= NULL since we are doing live SELL)
		$tmp = dber_checks::cost_type_paid($properties['cost_paid_type']);
		$dsql->add_text_part('by_the_day',$tmp);

	// elevator type id
		$tmp = dber_checks::db_relation($properties['elevator_type_id'],'flatdata.indelev');
		$dsql->add_text_part('id_elev',$tmp);

	// enter type id
		$tmp = dber_checks::db_relation($properties['enter_type_id'],'flatdata.indenter');
		$dsql->add_text_part('id_enter',$tmp);

	// geyser type id
		$tmp = dber_checks::db_relation($properties['geyser_type_id'],'flatdata.indgaz');
		$dsql->add_text_part('id_gaz',$tmp);

	// house type id
		$tmp = dber_checks::db_relation($properties['house_type_id'],'flatdata.indhtyp');
		$dsql->add_text_part('id_htyp',$tmp);

	// hotwater type id
		$tmp = dber_checks::db_relation($properties['hotwater_type_id'],'flatdata.indhwat');
		$dsql->add_text_part('id_hotw',$tmp);

	// humans live
		$tmp = dber_checks::integer($properties['humans_live']);
		$dsql->add_text_part('c_liv',$tmp);

	// !! info added from
		$tmp = dber_checks::info_added_from($properties['info_added_from']);
		$dsql->add_text_part('ofrom','es');
		$dsql->add_text_part('info_added_from',$tmp);

	// flat_type
		$tmp = dber_checks::flat_type($properties['flat_type']);
		$dsql->add_text_part('flrom',$tmp);

	// floor_type_id
		$tmp = dber_checks::db_relation($properties['floor_type_id'],'flatdata.indfloor');
		$dsql->add_text_part('id_floor',$tmp);

	// loggia type id
		$tmp = dber_checks::db_relation($properties['loggia_type_id'],'flatdata.indlod');
		$dsql->add_text_part('id_lod',$tmp);

	// notes
		$tmp = dber_checks::notes($properties['notes']);
		$dsql->add_text_part('notes',$tmp);

	// phone_exist
		$tmp = dber_checks::yn($properties['phone_exist']);
		$dsql->add_text_part('phone',$tmp);

	// privatize type id
		$tmp = dber_checks::db_relation($properties['privatize_type_id'],'flatdata.indprivl');
		$dsql->add_text_part('id_privl',$tmp);

	// quality type id
		$tmp = dber_checks::db_relation($properties['quality_type_id'],'flatdata.indstand');
		$dsql->add_text_part('id_stand',$tmp);

	// rooms count
		$tmp = dber_checks::integer($properties['rooms_count']);
		$dsql->add_text_part('c_rooms',$tmp);

		$tmp = dber_checks::integer($properties['rooms_selling_count']);
		$dsql->add_text_part('i_rooms',$tmp);

	// seller name
		$tmp = dber_checks::seller_name($properties['seller_name']);
		$dsql->add_text_part('a_name',$tmp);

		$tmp = dber_checks::phone_number($properties['seller_phone']);
		$dsql->add_text_part('a_tel',$tmp);

	// storeys
		$tmp = dber_checks::integer($properties['storey']);
		$dsql->add_text_part('floor',$tmp);

		$tmp = dber_checks::integer($properties['storey_total']);
		$dsql->add_text_part('c_floor',$tmp);

		$tmp = dber_checks::yn($properties['storey_double']);
		$dsql->add_text_part('two_fl',$tmp);

	// subway id
		$tmp = dber_checks::db_relation($properties['subway_id'],'flatdata.indsubw');
		$dsql->add_text_part('id_subw',$tmp);

	// subway length
		$tmp = dber_checks::float($properties['subway_length']);
		$dsql->add_text_part('len',$tmp);

		$tmp = dber_checks::db_relation($properties['subway_length_method_id'],'flatdata.indlen');
		$dsql->add_text_part('id_len',$tmp);

	// sqears
		$tmp = dber_checks::float($properties['sqear_full']);
		$dsql->add_text_part('fsqear',$tmp);

		$tmp = dber_checks::float($properties['sqear_live']);
		$dsql->add_text_part('lsqear',$tmp);

		$tmp = dber_checks::float($properties['sqear_kitchen']);
		$dsql->add_text_part('ksqear',$tmp);

		$tmp = dber_checks::float_extended($properties['sqear_rooms']);
		$dsql->add_text_part('rsqear',$tmp);

	// toilet type id
		$tmp = dber_checks::db_relation($properties['toilet_type_id'],'flatdata.indtoil');
		$dsql->add_text_part('id_toil',$tmp);

	// window type id
		$tmp = dber_checks::db_relation($properties['window_type_id'],'flatdata.indwindow');
		$dsql->add_text_part('id_window',$tmp);

	// some other
		$dsql->add_text_part('arend','Y');
		$dsql->add_mysql_part('idate','now()');
		$dsql->add_mysql_part('itime','now()');


	return "INSERT DELAYED INTO `flatdata`.`livtrad` SET " . $dsql->get_complete_string();
}

?>
