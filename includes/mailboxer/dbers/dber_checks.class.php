<?php
class dber_checks
{
	function addr($addr,$addr_street_id=false,$addr_house=false,$addr_korp=false) //{{{
	{
		// checking address
		$arr = [];

		$addr_street_id = intval($addr_street_id);
		$addr_house = intval($addr_house);
		$addr_korp = intval($addr_korp);

		if($addr_street_id < 1 || $addr_street_id == '')
		{
			$addr_street_id = 1;
		}

		if($addr_street_id != 1)
		{
			if($addr_hosue < 1 || $addr_house == '')
			{
				$addr_house = NULL;
			}
			if($addr_korp < 1 || $addr_korp == '')
			{
				$addr_korp = NULL;
			}
		}

		array_push($arr,trim($addr));
		array_push($arr,$addr_street_id);
		array_push($arr,$addr_house);
		array_push($arr,$addr_korp);

		return $arr;
	} //}}}
	function user_id($user_id) //{{{
	{
		//checking id agent

		return $user_id;
	} //}}}
	function info_added_from($value) //{{{
	{
		// checking info added from
		switch($value):
			case 'bsnrobot':
				return 'bsnrobot';
			default:
				return NULL;
		endswitch;
	} //}}}
	function db_relation($value,$table,$idname = 'id') //{{{
	{
		global $db;

		$sql = 'select 1 from ' . $table . ' where ' . $idname . ' = \'' . $value . '\' limit 1';
		$db->querys($sql);

		return $db->affected_rows == 1 ? $value : NULL;
	}//}}}
	function flat_type($value) //{{{
	{
		switch($value)
		{
			case 'F': return 'F';
			case 'R': return 'R';
			case 'H': return 'H';
			case 'K': return 'K';
			default: return 'F';
		}
	} //}}}
	function notes($value) //{{{
	{
		// check notes

		return $value;
	} //}}}
	function seller_name($value) //{{{
	{
		// checking user name

		if($value == '') $value = NULL;

		return $value;
	} //}}}
	function cost_type_paid($value) //{{{
	{
		switch(strtoupper($value))
		{
			case 'MONTH':
				return 'N';
			case 'DAY':
				return 'Y';
			default:
				return 'N';
		}
	} //}}}

	function yn($value) //{{{
	{
		if($value == 'Y' || $value == '1')
		{
			return 'Y';
		}
		else
		{
			return 'N';
		}
	} //}}}
	function integer($value) //{{{
	{
		// checking integer

		$value = preg_replace("/[^0-9]*/",'',$value);
		$value = intval($value);

		if($value != '')
		{
			return $value;
		}
		else
		{
			return NULL;
		}
	} //}}}
	function float($value) //{{{
	{
		// checking float

		$value = str_replace(',','.',$value);
		$value = preg_replace("/[^0-9\.]*/",'',$value);
		$value = floatval($value);

		if($value != '')
		{
			return $value;
		}
		else
		{
			return NULL;
		}
	}//}}}
	function float_extended($value) //{{{
	{
		$value = str_replace(',','.',$value);
		$value = preg_replace("/[^0-9\.\+\(\)]*/",'',$value);

		if($value != '')
		{
			return $value;
		}
		else
		{
			return NULL;
		}
	} //}}}
	function phone_number($value) //{{{
	{
		// checking phone_number

		if($value == '') $value = NULL;

		return $value;
	} //}}}
	function month($value) //{{{
	{
		// checking month number
		$value = intval($value);
		if(($value >= 1) && ($value <= 12))
		{
			return $value;
		}
		else
		{
			return NULL;
		}
	}
	function year($value) //{{{
	{
		// checking year
		$value = intval($value);
		if(($value >= 1900) && ($value <= 3000))
		{
			return (string) $value;
		}
		else
		{
			return NULL;
		}
	}
	function flat_type_build($value) //{{{
	{
		// checking flat type
		if(!is_null($value) && ($value != ''))
		{
			return $value;
		}
		else
		{
			return NULL;
		}
	}
	function house_section($value) //{{{
	{
		// checking house section
		if(!is_null($value) && ($value != ''))
		{
			return $value;
		}
		else
		{
			return NULL;
		}
	}
	function build_year($value) //{{{
	{
		// checking year
		$value = intval($value);
		if(($value >= 1900) && ($value <= intval(date('Y'))))
		{
			return (string) $value;
		}
		else
		{
			return NULL;
		}
	}
	
	function string($value) //{{{
	{
		// checking string
		$value = trim($value);
		if(!empty($value))
		{
			return $value;
		}
		else
		{
			return NULL;
		}
	}
}
?>
