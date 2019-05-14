<?php
function make_menu($page, $mapping, $requested_path='', $path='', $level=0){
    $menu = array();
    foreach($mapping as $key=>$item){
        if(!empty($item['menu'])){
            $item_path = ltrim(rtrim($path,'/').'/','/').$key;
            
            $requested=explode('/',$requested_path);
            //просматривать requested_path будем справа
            $requested=array_reverse($requested);
            $requested_key = array();
            $opened=true;
            if (Validate::isDigit($key))
                //просматриваем requested_url справа в поисках key
                foreach($requested as $r_key=>$r_item){
                    //ищем первое встретившееся число
                    if (Validate::isDigit($r_item)){
                        $requested_key[]=$r_item;
                        if ($r_item==$key){
                            //если нашли, устанавливаем флаг и выходим
                            $opened=true;
                            break;
                        } 
                        //если не нашли, значит key отсутствует, устанавливаем флаг и выходим
                        else{
                            $opened=false;
                            break;
                        } 
                    }
                }
            
            $menu_item = array(
                'title' => $item['title'],
                'url' => 'admin/'.$item_path,
                'level' => $level,
                'class' => !empty($item['class'])?$item['class']:'',
                'active' => $requested_path == 'admin/'.$item_path,
                'opened' => ((strpos($requested_path,$item_path) == 6  )&&
                             ( !empty($requested_key) && Validate::isDigit($key) ? $opened : ((count($requested_key)>1)?FALSE:TRUE) )
                            )
            );
            if($page->checkAccess($menu_item['url'])){
                $menu[] = $menu_item;
                if(!empty($item['childs']) && substr($requested_path,6,strlen($item_path))==$item_path) $menu = array_merge($menu, make_menu($page, $item['childs'],$requested_path,$item_path,$level+1));
            }
        }
    }
    return $menu;
}



/**
 * Выбора элементов массива по битовой маске
 * @param integer $val  значение маски
 * @param array $array  исходный массив данных
 * @param string $output - форма вывода данных (именованный массив и массив состояний 1/0 )
 * @return array
 */
 
function getArrayFromBitMask($val,$array, $output){
	//массив возвращаемых значений
	$bitArray = array();
	$stringArray = array();
	//максимальное значение степени двойки 
	$maxPow = count($array)-1;
	
	for($i=$maxPow; $i>=0; $i--){
		if($val>=pow(2,$i)){
			$val = $val - pow(2,$i);
			$bitArray[$i] = 1;
			$stringArray[$i] = $array[$i];
		} else  {
			$bitArray[$i] = 0;
			$stringArray[$i] = '';
		}
	}
	ksort($bitArray);
	ksort($stringArray);
	return $output=='array'?$stringArray:$bitArray;
}

/* Определение маски по массиву 
 * @param array $array - массив состояний (пример array(1,0,0,1))
 * @return array
 */
function setBitMaskByArray($array){
	$mask = 0;
	$i=0;
	foreach($array as $val){
		if($val>0) $mask = $mask + pow(2,$i);
		$i++;
	}
	return $mask;
}

?>