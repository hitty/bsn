<?php
require_once('/home/bsnrobot/function_utf.php');
require_once('/home/bsnrobot/dbers/dber_checks.class.php');
require_once('/home/bsnrobot/dbers/dithered_sql.class.php');
require_once('/home/bsnrobot/dbers/function.grab_all_attributes.php');


function dber_estate_commercial_remove($user_id, $remove_options)
{
	global $db;

	if($remove_options == REMOVE_ROBOT_ONLY)
	{
		$sql = "DELETE from flatdata.commtrad where user_id = '" . $user_id . "' AND info_added_from = 'bsnrobot';";
	}
	else if($remove_options == REMOVE_ALL)
	{
		$sql = "DELETE from flatdata.commtrad where user_id = '" . $user_id . "';";
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
function dber_estate_commercial($item_node)
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

	// building type id
		$tmp = dber_checks::db_relation($properties['building_type_id'],'flatdata.indtypc');
		$dsql->add_text_part('id_typc',$tmp);

	// confirmation status
		$tmp = dber_checks::yn($properties['confirm_status']);
		$dsql->add_text_part('sel_allow',$tmp);
		$dsql->add_text_part('actual',$tmp);

	// cost
		$tmp = dber_checks::float($properties['cost']);
		$dsql->add_text_part('cost',$tmp);

	// deal type id
		$tmp = dber_checks::db_relation($properties['deal_type_id'],'flatdata.indarend');
		$dsql->add_text_part('id_arend',$tmp);

	// enter type id
		$tmp = dber_checks::db_relation($properties['enter_type_id'],'flatdata.indenter');
		$dsql->add_text_part('id_enter',$tmp);

	// estate rights type id
		$tmp = dber_checks::db_relation($properties['estate_rights_type_id'],'flatdata.indpriv');
		$dsql->add_text_part('id_priv',$tmp);

	// house type id
		$tmp = dber_checks::db_relation($properties['house_type_id'],'flatdata.indhtyp');
		$dsql->add_text_part('id_htype',$tmp);

	// !! info added from
		$tmp = dber_checks::info_added_from($properties['info_added_from']);
		$dsql->add_text_part('ofrom','es');
		$dsql->add_text_part('info_added_from',$tmp);

	// notes
		$tmp = dber_checks::notes($properties['notes']);
		$dsql->add_text_part('notes',$tmp);

	// object type id
		$tmp = dber_checks::db_relation($properties['object_type_id'],'flatdata.indobject');
		$dsql->add_text_part('id_object',$tmp);

	// phone lines count
		$tmp = dber_checks::integer($properties['phone_lines_count']);
		$dsql->add_text_part('c_phone',$tmp);

	// purpose type id
		$tmp = dber_checks::db_relation($properties['purpose_type_id'],'flatdata.indnazn');
		$dsql->add_text_part('id_nazn',$tmp);

	// rooms count
		$tmp = dber_checks::integer($properties['rooms_count']);
		$dsql->add_text_part('c_rooms',$tmp);

	// seller name
		$tmp = dber_checks::seller_name($properties['seller_name']);
		$dsql->add_text_part('a_name',$tmp);

	// seller phone
		$tmp = dber_checks::phone_number($properties['seller_phone']);
		$dsql->add_text_part('a_tel',$tmp);

	// storeys
		$tmp = dber_checks::integer($properties['storey_start']);
		$dsql->add_text_part('b_floor',$tmp);

		$tmp = dber_checks::integer($properties['storey_end']);
		$dsql->add_text_part('e_floor',$tmp);

		$tmp = dber_checks::integer($properties['storey_total']);
		$dsql->add_text_part('c_floor',$tmp);

	// subway id
		$tmp = dber_checks::db_relation($properties['subway_id'],'flatdata.indsubw');
		$dsql->add_text_part('id_subw',$tmp);

	// subway length
		$tmp = dber_checks::float($properties['subway_length']);
		$dsql->add_text_part('len',$tmp);

		$tmp = dber_checks::db_relation($properties['subway_length_method_id'],'flatdata.indlen');
		$dsql->add_text_part('id_len',$tmp);

	// sqears
		$tmp = dber_checks::float($properties['sqear_full_start']);
		$dsql->add_text_part('b_fsqear',$tmp);

		$tmp = dber_checks::float($properties['sqear_full_end']);
		$dsql->add_text_part('e_fsqear',$tmp);

		$tmp = dber_checks::float($properties['sqear_usefull']);
		$dsql->add_text_part('nsqear',$tmp);

		$tmp = dber_checks::float($properties['sqear_area']);
		$dsql->add_text_part('gsqear',$tmp);

		$tmp = dber_checks::db_relation($properties['sqear_area_type_id'],'flatdata.indsqear');
		$dsql->add_text_part('id_sqear',$tmp);

		$tmp = dber_checks::float($properties['sqear_building']);
		$dsql->add_text_part('gsqear_str',$tmp);

		$tmp = dber_checks::db_relation($properties['sqear_building_type_id'],'flatdata.indsqear_str');
		$dsql->add_text_part('id_sqear_str',$tmp);


	// some other
		$dsql->add_mysql_part('idate','now()');
		$dsql->add_mysql_part('itime','now()');


	return "INSERT DELAYED INTO `flatdata`.`commtrad` SET " . $dsql->get_complete_string();
}

?>
