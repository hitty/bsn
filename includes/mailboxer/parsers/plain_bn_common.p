<?php

class plain_bn
{
	static function grabStoreys($value)
	{
		$return = array();

		if($tmp = sscanf($value, "%d/%d"))
		{
			$return[0] = $tmp[0]; 		// storey
			$return[1] = $tmp[1]; 		// storey_total
		}
		else
		{
			$return[0] = (int) $value; 	// storey
			$return[1] = NULL;			// storey_total
		}

		return $return;
	}

	static function plusminus($value)
	{
		return ($value == '+') ? 'Y' : 'N';
	}

	static function eval_sqear($value)
	{
		$return = 0;

		preg_match_all("/([0-9\,\.]+)/",$value,$matches);

		foreach($matches[1] as $match)
		{
			$return += @floatval($match);
		}

		return $return;
	}

	static function get_sqear_area_type($value)
	{
		if(preg_match("/кв\..*м/i",$value))
		{
			return 2;
		}
		else if(preg_match("/га/i",$value))
		{
			return 4;
		}
		else if(preg_match("/сот/i",$value))
		{
			return 3;
		}
		else return 4;
	}

	static function get_house_type_id($value)
	{
		if(trim($value) == '') return NULL;

		global $db;
		global $config;

		$return = $config['db_house_types_default']; // default house type

		if(!isset($db)) throw new Exception('DB class must exist here!!');

		$result = $db->query('select `'.$config['db_house_types_table_id_fild'].'` from `'.$config['db_database'].'`.`'.$config['db_house_types_table'].'` where `'.$config['db_house_types_table_bn_fild'].'` = \''.$value.'\' or `'.$config['db_house_types_table_small_title_fild'].'` = \''.$value.'\' or `'.$config['db_house_types_table_title_fild'].'` = \''.$value.'\' LIMIT 1;');
		if($db->affected_rows == 1)
		{
			list($return) = $result->fetch_array(MYSQL_NUM);
		}

		return $return;
	}

	static function get_enter_type_id($value)
	{
		if(trim($value) == '') return NULL;

		global $db;
		global $config;

		if(!isset($db)) throw new Exception('DB class must exist here!!');
		$result = $db->query('select `'.$config['db_enter_table_id_fild'].'`,`'.$config['db_enter_table_bn_fild'].'` from `'.$config['db_database'].'`.`'.$config['db_enter_table'].'` where `'.$config['db_enter_table_bn_fild'].'` IS NOT NULL');
		if($db->affected_rows > 0)
		{
			while($res = $result->fetch_array(MYSQL_NUM))
			{
				$tmp = explode('|',$res[1]);
				foreach($tmp as $tmpp)
				{
					if($tmpp == $value)
					{
						return $res[0];
					}
				}
			}
		}

		return $config['db_enter_default'];
	}

	static function get_rooms_count_humans_live($value)
	{
		$return[0] = NULL;
		$return[1] = NULL;

		if($tmp = sscanf($value,"%d/%d/%d"))
		{
			$return[0] = $tmp[0]; // rooms count
			$return[1] = $tmp[1]; // total humans live
		}

		return $return;
	}

	static function get_subway_id($value)
	{
		if(trim($value) == '') return NULL;
		global $db;
		global $config;

		$return = array();

		$return[0] = $config['db_subway_default']; // default value

		preg_match("/^(.*?)(?:\ ([0-9]+)\ ([^0-9]+))?$/", $value, $matches);
		if(!isset($matches[2]) && !isset($matches[3]))
		{
			$matches[2] = NULL;
			$matches[3] = NUlL;
			if(preg_match("/^(.*?)\ [\ 0-9]+$/",$matches[1],$matches_st2))
			{
				$matches[1] = $matches_st2[1];
			}
		}

		// analazing subway
			$val_subw = $matches[1];

			if(!isset($db)) throw new Exception('DB class must exist here!!');

			$result = $db->query('select `'.$config['db_subway_table_id_fild'].'` from `'.$config['db_database'].'`.`'.$config['db_subway_table'].'` where `'.$config['db_subway_table_bn_fild'].'` = \''.$val_subw.'\' or `'.$config['db_subway_table_small_title_fild'].'` = \''.$val_subw.'\' or `'.$config['db_subway_table_title_fild'].'` = \''.$val_subw.'\' LIMIT 1;');

			if($db->affected_rows == 1)
			{
				list($return[0]) = $result->fetch_array(MYSQL_NUM);
			}
			else // if we have not find any equal string, we'll do similarity analize
			{
				$similarity_analize = new similarity_analize($config['db_similarity_analize_min_percent'],$config['db_similarity_analize_min_distance']);

				$result = $db->query('select `'.$config['db_subway_table_bn_fild'].'`, `'.$config['db_subway_table_id_fild'].'` from `'.$config['db_database'].'`.`'.$config['db_subway_table'].'`;');

				while($res = $result->fetch_array(MYSQL_NUM))
				{
					$similarity_analize->add_pair($val_subw,$res[0],$res[1]);
				}

				if($similars = $similarity_analize->get_similar_pair())
				{
					$return[0] = $similars->pair_id;
				}

				unset($similarity_analize);
			}

		// analizing subway length and subway length  method if present
			$return[1] = NULL; // default value (subway_length)
			$return[2] = NULL; // default value (subway_length_method)
			if(!is_null($matches[2]) && !is_null($matches[3]))
			{
				switch($matches[3]):
					case 'пеш':
						$return[2] = 2; // минут пешком, indlen
						$return[1] = $matches[2];
						break;
					case 'тр':
						$return[2] = 3; // минут на транспорте
						$return[1] = $matches[2];
						break;
				endswitch;
			}
			else
			{
				$return[1] = NULL;
				$return[2] = NULL;
			}


		return $return;
	}

	static function get_block_id($value)
	{
		if(trim($value) == '') return NULL;

		global $db;
		global $config;

		$return = $config['db_block_default']; // default block

		$result = $db->query('select `'.$config['db_block_table_id_fild'].'` from `'.$config['db_database'].'`.`'.$config['db_block_table'].'` where `'.$config['db_block_table_bn_fild'].'` = \''.$value.'\' or `'.$config['db_block_table_small_title_fild'].'` = \''.$value.'\' or `'.$config['db_block_table_title_fild'].'` = \''.$value.'\' LIMIT 1;');

		if($db->affected_rows == 1)
		{
			list($return) = $result->fetch_array(MYSQL_NUM);
		}
		else // if we have not find any equal string, we'll do similarity analize
		{
			$similarity_analize = new similarity_analize($config['db_similarity_analize_min_percent'],$config['db_similarity_analize_min_distance']);

			$result = $db->query('select `'.$config['db_block_table_title_fild'].'`, `'.$config['db_block_table_id_fild'].'` from `'.$config['db_database'].'`.`'.$config['db_block_table'].'`;');

			while($res = $result->fetch_array(MYSQL_NUM))
			{
				$similarity_analize->add_pair($value,$res[0],$res[1]);
			}

			if($similars = $similarity_analize->get_similar_pair())
			{
				$return = $similars->pair_id;
			}

			unset($similarity_analize);
		}

		return $return;
	}

	static function get_block_country_id($value)
	{
		if(trim($value) == '') return NULL;

		global $db;
		global $config;

		$return = $config['db_block_country_default']; // default block

		$result = $db->query('select `'.$config['db_block_country_table_id_fild'].'` from `'.$config['db_database'].'`.`'.$config['db_block_country_table'].'` where `'.$config['db_block_country_table_title_fild'].'` = \''.$value.'\' LIMIT 1;');

		if($db->affected_rows == 1)
		{
			list($return) = $result->fetch_array(MYSQL_NUM);
		}
		else // if we have not find any equal string, we'll do similarity analize
		{
			$similarity_analize = new similarity_analize($config['db_similarity_analize_min_percent'],$config['db_similarity_analize_min_distance']);

			$result = $db->query('select `'.$config['db_block_country_table_title_fild'].'`, `'.$config['db_block_country_table_id_fild'].'` from `'.$config['db_database'].'`.`'.$config['db_block_country_table'].'`;');

			while($res = $result->fetch_array(MYSQL_NUM))
			{
				$similarity_analize->add_pair($value,$res[0],$res[1]);
			}

			if($similars = $similarity_analize->get_similar_pair())
			{
				$return = $similars->pair_id;
			}

			unset($similarity_analize);
		}

		return $return;
	}

	static function get_object_type_id($value)
	{
		if(trim($value) == '') return NULL;

		global $db;
		global $config;

		$return = $config['db_object_type_default']; // default block

		$result = $db->query('select `'.$config['db_object_type_table_id_fild'].'` from `'.$config['db_database'].'`.`'.$config['db_object_type_table'].'` where `'.$config['db_object_type_table_title_fild'].'` = \''.$value.'\' LIMIT 1;');

		if($db->affected_rows == 1)
		{
			list($return) = $result->fetch_array(MYSQL_NUM);
		}
		else // if we have not find any equal string, we'll do similarity analize
		{
			$similarity_analize = new similarity_analize($config['db_similarity_analize_min_percent'],$config['db_similarity_analize_min_distance']);

			$result = $db->query('select `'.$config['db_object_type_table_title_fild'].'`, `'.$config['db_object_type_table_id_fild'].'` from `'.$config['db_database'].'`.`'.$config['db_object_type_table'].'`;');

			while($res = $result->fetch_array(MYSQL_NUM))
			{
				$similarity_analize->add_pair($value,$res[0],$res[1]);
			}

			if($similars = $similarity_analize->get_similar_pair())
			{
				$return = $similars->pair_id;
			}

			unset($similarity_analize);
		}

		return $return;
	}

	static function get_toilet_id($value)
	{
		if(trim($value) == '') return NULL;

		global $db;
		global $config;

		$return = $config['db_toilet_default']; // default toilet

		$result = $db->query('select `'.$config['db_toilet_table_id_fild'].'` from `'.$config['db_database'].'`.`'.$config['db_toilet_table'].'` where `'.$config['db_toilet_table_bn_fild'].'` = \''.$value.'\' LIMIT 1;');
		if($db->affected_rows == 1)
		{
			list($return) = $result->fetch_array(MYSQL_NUM);
		}

		return $return;
	}
	
	static function get_area_rights_type_id($value)
	{
		if(trim($value) == '') return NULL;

		global $db;
		global $config;

		$return = $config['db_area_rights_type_default']; // default block

		$result = $db->query('select `'.$config['db_area_rights_type_table_id_fild'].'` from `'.$config['db_database'].'`.`'.$config['db_area_rights_type_table'].'` where `'.$config['db_area_rights_type_table_bn_fild'].'` = \''.$value.'\' LIMIT 1;');

		if($db->affected_rows == 1)
		{
			list($return) = $result->fetch_array(MYSQL_NUM);
		}

		return $return;
	}
	
	static function get_house_wall_fabric_type_id($value)
	{
		if(trim($value) == '') return NULL;

		global $db;
		global $config;

		$return = $config['db_house_wall_fabric_type_default']; // default block

		$result = $db->query('select `'.$config['db_house_wall_fabric_type_table_id_fild'].'` from `'.$config['db_database'].'`.`'.$config['db_house_wall_fabric_type_table'].'` where `'.$config['db_house_wall_fabric_type_table_bn_fild'].'` = \''.$value.'\' LIMIT 1;');

		if($db->affected_rows == 1)
		{
			list($return) = $result->fetch_array(MYSQL_NUM);
		}

		return $return;
	}
	
	static function get_heating_type_id($value)
	{
		if(trim($value) == '') return NULL;

		global $db;
		global $config;

		if (trim($value) == '+') $return = 2;
		elseif (trim($value) == '-') $return = 3;
		else $return = 1;
		
		return $return;
	}
	
	static function get_wiring_type_id($value)
	{
		if(trim($value) == '') return NULL;

		global $db;
		global $config;

		if (trim($value) == '+') $return = 2;
		elseif (trim($value) == '-') $return = 3;
		else $return = 1;
		
		return $return;
	}
	
	static function get_drinking_water_type_id($value)
	{
		if(trim($value) == '') return NULL;

		global $db;
		global $config;

		if (trim($value) == '+') $return = 2;
		elseif (trim($value) == '-') $return = 3;
		else $return = 1;
		
		return $return;
	}
}


function checkAttrCount(&$node)
{
	$filds = array(
					'addr',
					'addr_street_id',
					'addr_house',
					'addr_korp',
					'agent_id',
					'block_id',
					'ceiling_height',
					'confirm_status',
					'cost',
					'elevator_exist',
					'enter_type_id',
					'geyser_type_id',
					'hotwater_type_id',
					'humans_live',
					'info_added_from',
					'flat_type',
					'floor_type_id',
					'loggia_exist',
					'notes',
					'phone_exist',
					'quality_state_id',
					'rooms_count',
					'rooms_selling_count',
					'privatize_state_id',
					'seller_name',
					'seller_phone',
					'storey',
					'storey_total',
					'storey_double',
					'subway_id',
					'subway_length',
					'subway_length_method',
					'sqear_full',
					'sqear_live',
					'sqear_kitchen',
					'sqear_rooms',
					'toilet_id',
					'window_id');

	foreach($filds as $fild)
	{
		if(!$node->hasAttribute($fild))
		{
			$no[] = $fild;
		}
	}

	if(isset($no))
	{
		$nostr = implode("\n",$no);
		throw new Exception('Some filds not finded:'."\n".$nostr."\n");
	}
}


?>