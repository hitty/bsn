<?php
function grab_all_attributes(&$node)
{
	foreach($node->attributes as $attribute)
	{
		$return[$attribute->nodeName] = $attribute->nodeValue;
	}

	return $return;
}
?>
