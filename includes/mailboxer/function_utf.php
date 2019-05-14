<?php

function utf($str, $encoding = 'koi8-r')
{
	return @iconv($encoding,'utf-8//IGNORE',$str);
}

function koi($str, $encoding = 'utf-8')
{
	return @iconv($encoding,'koi8-r//IGNORE',$str);
}

function convert_array_utf_koi(&$array)
{
	if(!function_exists('convert_array_utf_koi_recursion'))
	{
		function convert_array_utf_koi_recursion(&$item,&$key)
		{
			$item = koi($item);
			$key = koi($key);
		}
	}

	array_walk_recursive(&$array,'convert_array_utf_koi_recursion');
}
?>
