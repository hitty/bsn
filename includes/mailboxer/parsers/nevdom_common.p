<?php

class plain_nevdom
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

	static function get_block_id($value)
	{
		if(trim($value) == '') return NULL;

		global $db;
		global $config;

		$return = $config['db_block_default']; // default block

		$result = $db->querys('select `'.$config['db_block_table_id_fild'].'` from `'.$config['db_database'].'`.`'.$config['db_block_table'].'` where `'.$config['db_block_table_bn_fild'].'` = \''.$value.'\' or `'.$config['db_block_table_small_title_fild'].'` = \''.$value.'\' or `'.$config['db_block_table_title_fild'].'` = \''.$value.'\' LIMIT 1;');

		if($db->affected_rows == 1)
		{
			list($return) = $result->fetch_array(MYSQL_NUM);
		}
		else // if we have not find any equal string, we'll do similarity analize
		{
			$similarity_analize = new similarity_analize($config['db_similarity_analize_min_percent'],$config['db_similarity_analize_min_distance']);

			$result = $db->querys('select `'.$config['db_block_table_title_fild'].'`, `'.$config['db_block_table_id_fild'].'` from `'.$config['db_database'].'`.`'.$config['db_block_table'].'`;');

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
}
?>
