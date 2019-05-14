<?php
require_once('/home/bsnrobot/function_utf.php');
require_once('/home/bsnrobot/dbers/dber_checks.class.php');
require_once('/home/bsnrobot/dbers/dithered_sql.class.php');
require_once('/home/bsnrobot/dbers/function.grab_all_attributes.php');


function dber_estate_country_sell_remove($user_id, $remove_options)
{
	global $db;

	if($remove_options == REMOVE_ROBOT_ONLY)
	{
		$sql = "DELETE from flatdata.viltrad where user_id = '" . $user_id . "' AND info_added_from = 'bsnrobot';";
	}
	else if($remove_options == REMOVE_ALL)
	{
		$sql = "DELETE from flatdata.viltrad where user_id = '" . $user_id . "';";
	}


	if($db->query($sql))
	{
		return $db->affected_rows;
	}
	else
	{
		return false;
	}
}
function dber_estate_country_sell($item_node)
{
	global $db;

	$properties = grab_all_attributes($item_node);
	convert_array_utf_koi($properties);


		$dsql = new dithered_sql;

	// address
		$tmp = dber_checks::string($properties['addr']);
		$dsql->add_text_part('addr',$tmp);
		
	// arend
		$tmp = dber_checks::yn($properties['arend']);
		$dsql->add_text_part('arend',$tmp);

	// user id
		$tmp = dber_checks::user_id($properties['user_id']);
		$dsql->add_text_part('user_id',$tmp);

	// area_state_type
		$tmp = dber_checks::db_relation($properties['area_state_type_id'],'flatdata.indgarden');
		$dsql->add_text_part('id_garden',$tmp);

	// block_country_id
		$tmp = dber_checks::db_relation($properties['block_country_id'],'flatdata.indlenobl');
		$dsql->add_text_part('id_lenobl',$tmp);
	
	// build year
		$tmp = dber_checks::build_year($properties['build_year']);
		$dsql->add_text_part('byear',$tmp);
		
	// build_rights_type_id
		$tmp = dber_checks::db_relation($properties['build_rights_type_id'],'flatdata.indstroi');
		$dsql->add_text_part('id_stroi',$tmp);

	// confirmation status
		$tmp = dber_checks::yn($properties['confirm_status']);
		$dsql->add_text_part('sel_allow',$tmp);
		$dsql->add_text_part('actual',$tmp);

	// cost
		$tmp = dber_checks::float($properties['cost']);
		$dsql->add_text_part('cost',$tmp);

	// drinking water type id
		$tmp = dber_checks::db_relation($properties['drinking_water_type_id'],'flatdata.indhwat');
		$dsql->add_text_part('id_hwat',$tmp);
	
	// garage type id
		$tmp = dber_checks::db_relation($properties['garage_type_id'],'flatdata.indgarazh');
		$dsql->add_text_part('id_garazh',$tmp);
	
	// house geyser type id
		$tmp = dber_checks::db_relation($properties['house_geyser_type_id'],'flatdata.indgaz');
		$dsql->add_text_part('id_gaz',$tmp);
	
	// house wall fabric type id
		$tmp = dber_checks::db_relation($properties['house_wall_fabric_type_id'],'flatdata.indhmat');
		$dsql->add_text_part('id_hmat',$tmp);
	
	// house roof fabric type id
		$tmp = dber_checks::db_relation($properties['house_roof_fabric_type_id'],'flatdata.indhroof');
		$dsql->add_text_part('id_hroof',$tmp);
	
	// house heating type id
		$tmp = dber_checks::db_relation($properties['house_heating_type_id'],'flatdata.indhwarm');
		$dsql->add_text_part('id_hwarm',$tmp);
	
	// house wiring type id
		$tmp = dber_checks::db_relation($properties['house_wiring_type_id'],'flatdata.indhel');
		$dsql->add_text_part('id_hel',$tmp);
	
	// house bath type id
		$tmp = dber_checks::db_relation($properties['house_bath_type_id'],'flatdata.indbath');
		$dsql->add_text_part('id_bath',$tmp);

	// !! info added from
		$tmp = dber_checks::info_added_from($properties['info_added_from']);
		$dsql->add_text_part('ofrom','es');
		$dsql->add_text_part('info_added_from',$tmp);

	// notes
		$tmp = dber_checks::notes($properties['notes']);
		$dsql->add_text_part('notes',$tmp);

	// object type id
		$tmp = dber_checks::db_relation($properties['object_type_id'],'flatdata.indtypv');
		$dsql->add_text_part('id_typv',$tmp);
	
	// phone type id
		$tmp = dber_checks::db_relation($properties['phone_type_id'],'flatdata.indphone');
		$dsql->add_text_part('id_phone',$tmp);
	
	// pond type id
		$tmp = dber_checks::db_relation($properties['pond_type_id'],'flatdata.indriver');
		$dsql->add_text_part('id_river',$tmp);

	// purpose type id
		$tmp = dber_checks::db_relation($properties['purpose_type_id'],'flatdata.indnaznach');
		$dsql->add_text_part('id_naznach',$tmp);

	// rooms count
		$tmp = dber_checks::integer($properties['rooms_count']);
		$dsql->add_text_part('c_rooms',$tmp);

	// residence permit type id
		$tmp = dber_checks::db_relation($properties['residence_permit_type_id'],'flatdata.indprop');
		$dsql->add_text_part('id_prop',$tmp);
	
	// seller name
		$tmp = dber_checks::seller_name($properties['seller_name']);
		$dsql->add_text_part('a_name',$tmp);

	// seller phone
		$tmp = dber_checks::phone_number($properties['seller_phone']);
		$dsql->add_text_part('a_tel',$tmp);

	// storey total
		$tmp = dber_checks::integer($properties['storey_total']);
		$dsql->add_text_part('floor',$tmp);

	// gd_station
		$tmp = dber_checks::string($properties['gd_station']);
		$dsql->add_text_part('gdstation',$tmp);
	
	// gd_station length
		$tmp = dber_checks::integer($properties['gd_station_length']);
		$dsql->add_text_part('leng',$tmp);

	// gd_station length method id
		$tmp = dber_checks::db_relation($properties['gd_station_length_method_id'],'flatdata.indlen');
		$dsql->add_text_part('id_len',$tmp);
	
	// readiness type id
		$tmp = dber_checks::db_relation($properties['readiness_type_id'],'flatdata.indpercent');
		$dsql->add_text_part('id_percent',$tmp);

	// sqears
		$tmp = dber_checks::float($properties['sqear_full']);
		$dsql->add_text_part('fsqear',$tmp);

		$tmp = dber_checks::float($properties['sqear_live']);
		$dsql->add_text_part('lsqear',$tmp);

		$tmp = dber_checks::float($properties['sqear_area']);
		$dsql->add_text_part('sqear',$tmp);

	// toilet type id
		$tmp = dber_checks::db_relation($properties['toilet_type_id'],'flatdata.indhtoil');
		$dsql->add_text_part('id_htoil',$tmp);
		
	// some other
		$dsql->add_mysql_part('idate','now()');
		$dsql->add_mysql_part('itime','now()');


	return "INSERT INTO `flatdata`.`viltrad` SET " . $dsql->get_complete_string();
}

?>
