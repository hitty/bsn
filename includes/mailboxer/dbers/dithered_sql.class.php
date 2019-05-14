<?php
class dithered_sql
{
	public $set_parts = [];
	private $def_db = 'flatdata';
	private $def_tbl = '';

	function add_post_part($db_name,$this_name = false)
	{
		if($this_name === false) $this_name = $db_name;

		array_push($this->set_parts,array($db_name,$this_name,0));
	}

	function add_text_part($db_name,$text)
	{
		if(!is_null($text))
		{
			array_push($this->set_parts,array($db_name,$text,1));
		}
		else
		{
			$this->add_mysql_part($db_name,'NULL');
		}
	}

	function add_mysql_part($db_name,$func)
	{
		array_push($this->set_parts,array($db_name,$func,2));
	}

	function get_complete_string($wich_needed = false, $not_needed = false)
	{
		$complete_string = '';

		foreach($this->set_parts as $part)
		{
			if($wich_needed && !in_array($part[0],$wich_needed))
			{
				continue;
			}
			if($not_needed && in_array($part[0],$not_needed))
			{
				continue;
			}
			if($part[2] === 0) // getpost
			{
				$name = '`' . $part[0] . '`';
				$value = getpost($part[1]);

				if(is_array($value))
				{
					$value = $value[0];
				}

				$value = isset($value) ? '\''.$value.'\'' : 'NULL';

			}
			else if($part[2] === 1) // mysql string
			{
				$name = '`' . $part[0] . '`';
				$value = '\'' . $part[1] . '\'';
			}
			else if($part[2] === 2) // mysql function
			{
				$name = '`' . $part[0] . '`';
				$value = $part[1];
			}

			$needed[] = $name . ' = ' . $value;
		}

		sort($needed);

		$complete_string = implode(', ',$needed);
		return $complete_string;
	}

	function get_table_part()
	{
		foreach($this->set_parts as $set_part)
		{
			$tmp[] = $set_part[0];
		}

		return "(`".implode("`,`",$tmp)."`)";
	}

	function get_values_part()
	{
		foreach($this->set_parts as $set_part)
		{
			if($set_part[2] == 1)
			{
				$tmp[] = "'".addslashes($set_part[1])."'";
			}
			else if($set_part[2] == 2)
			{
				$tmp[] = addslashes($set_part[1]);
			}
		}

		return "VALUES(".implode(',',$tmp).")";
	}
}
?>
