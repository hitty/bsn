<?php
class similarity_analize
{
	private $container = [];
	private $min_percent;
	private $min_distance;

	public function __construct($min_percent, $min_distance)
	{
		$this->min_percent = $min_percent;
		$this->min_distance = $min_distance;
	}

	public function add_pair($src_str, $targ_str, $unique_pair_id)
	{
		$tmp = similar_text($src_str, $targ_str, $percent);
		$idx = sizeof($this->container);
		$this->container[$idx] = new stdClass;
		$this->container[$idx]->source = $src_str;
		$this->container[$idx]->target = $targ_str;
		$this->container[$idx]->pair_id = $unique_pair_id;
		$this->container[$idx]->similarity = $tmp;
		$this->container[$idx]->similarity_percent = $percent;
	}

	private static function similarity_sort($a, $b)
	{
		if($a->similarity_percent == $b->similarity_percent) return 0;
		return ($a->similarity_percent > $b->similarity_percent) ? +1 : -1;
	}

	public function get_similar_pair($count = 1)
	{
		if($count < 1) throw new Exception('Cant return less than 1 pair');
		if(sizeof($this->container) < 4) throw new Exception('Cant do the similarity analize since have not at least 3 pairs');
		if((sizeof($this->container) - $count) < 2)
		{
			$count = sizeof($this->container) - 2;
		}

		usort($this->container,array('similarity_analize','similarity_sort'));

		for($i=1;$i<=$count;$i++)
		{
			$idx = sizeof($this->container) - $i;
			if($this->container[$idx]->similarity_percent >= $this->min_percent && ($this->container[$idx]->similarity_percent - $this->container[$idx-1]->similarity_percent) >= $this->min_distance)
			{
				return $this->container[$idx];
			}
			else
			{
				return false;
			}
		}
	}
}

define('LOWERCASE',3);
define('UPPERCASE',1);

function detect_cyr_charset($str, $count_chars = 0)
{
	$charsets = Array(
										'koi8-r' => 0,
										'cp1251' => 0,
										'cp866' => 0,
										'iso-8859-5' => 0,
										'maccyrillic' => 0
										);

	if ($count_chars == 0)
	{
		$limit = strlen($str);
	}
	else
	{
		$limit = $count_chars < strlen($str) ? $count_chars : strlen($str);
	}

	for ( $i = 0, $length = $limit; $i < $length; $i++ ) {
			$char = ord($str[$i]);
			//non-russian characters
			if ($char < 128 || $char > 256) continue;

			//CP866
			if (($char > 159 && $char < 176) || ($char > 223 && $char < 242))
					$charsets['cp866']+=LOWERCASE;
			if (($char > 127 && $char < 160)) $charsets['cp866']+=UPPERCASE;

			//KOI8-R
			if (($char > 191 && $char < 223)) $charsets['koi8-r']+=LOWERCASE;
			if (($char > 222 && $char < 256)) $charsets['koi8-r']+=UPPERCASE;

			//WIN-1251
			if ($char > 223 && $char < 256) $charsets['cp1251']+=LOWERCASE;
			if ($char > 191 && $char < 224) $charsets['cp1251']+=UPPERCASE;

			//MAC
			if ($char > 221 && $char < 255) $charsets['maccyrillic']+=LOWERCASE;
			if ($char > 127 && $char < 160) $charsets['maccyrillic']+=UPPERCASE;

			//ISO-8859-5
			if ($char > 207 && $char < 240) $charsets['iso-8859-5']+=LOWERCASE;
			if ($char > 175 && $char < 208) $charsets['iso-8859-5']+=UPPERCASE;

	}
	arsort($charsets);
	return key($charsets);
}

// string validity check
function is_valid($string, $min_count = 10, $min_rus_count = 0, $src_encoding = 'koi8-r')
{

	$tmp_1 = mb_strlen($string, $src_encoding);

	$string = iconv($src_encoding,"koi8-r//IGNORE",$string);

	if(strlen($string) != $tmp_1) return false;

	if (strlen($string) < $min_count) return false;

	if ($min_rus_count > 0)
	{
		$c = 0;
		for($i=0;$i<strlen($string);$i++)
		{
			//KOI8-R
			$char = ord($string[$i]);
			if (($char > 191 && $char < 223)) $c++;
			elseif (($char > 222 && $char < 256)) $c++;
		}

		if ($c < $min_rus_count) return false;
	}

	return true;
}
?>
