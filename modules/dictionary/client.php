<?php
require_once('includes/class.paginator.php');

// таблицы модуля
$sys_tables = Config::$sys_tables;
//читаем action - 'block' или выбранная буква
$action = empty($this_page->page_parameters[0]) ? "" : $this_page->page_parameters[0];

//от какой записи вести отчет
$from = 0;
//записей на страницу
$strings_per_page = Config::Get('view_settings/strings_per_page');

$this_page->addBreadcrumbs('Сервисы','service');
$this_page->addBreadcrumbs('Справочник','information');
$this_page->addBreadcrumbs('Словарь','dictionary');

$GLOBALS['css_set'][] = '/modules/dictionary/styles.css';
//транлитные варианты русских букв для адреса
$subst_ru = array('rA', 'rB', 'rV', 'rG', 'rD', 'rJe', 'rZh', 'rZ', 'rI', 'rK', 'rL', 'rM', 'rN', 'rO',
                      'rP', 'rR', 'rS', 'rT', 'rU', 'rF', 'rH', 'rC', 'rCh', 'rSh', 'rE', 'rJu', 'rJa');
//русские буквы
$arr_alph_ru = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'К', 'Л', 'М', 'Н', 'О',
                         'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Э', 'Ю', 'Я');
$arr_alph_en = range('A', 'Z');
switch(true){
    //###########################################################################
    // заглавная страница
    //###########################################################################
    case empty($action): 
        $module_template = 'mainpage.html';
            $h1 = empty($this_page->page_seo_h1) ? 'Словарь - Справочник' : $this_page->page_seo_h1;
            Response::SetString('h1', $h1);            
            $new_meta = array('title'=>$h1, 'description' =>$h1, true);
            $this_page->manageMetadata($new_meta, true);     
        break;

    //###########################################################################
    // выводим список букв
    //###########################################################################
    case ($action == 'block'): 
        if(!$this_page->first_instance) {
            $module_template = 'block.html';
            
            //выбираем первые буквы слов
            $list = $db->fetchall("SELECT DISTINCT LEFT(".$sys_tables['dictionary'].".word,1) AS letter FROM ".$sys_tables['dictionary']." ORDER BY letter");
            
            //распределяем буквы по типу
            $l_subst_ru = []; //транслитные варианты  русских букв
            $letters_ru = []; //выбранные русские буквы
            $letters_en = []; //выбранные английские буквы
            foreach($list AS $key => $lt)
            {
                $index = array_search($lt['letter'],$arr_alph_ru);
                if ($index!==false){
                    //если буква русская, то запоминаем ее и транслитный вариант
                    array_push($l_subst_ru,$subst_ru[$index]);
                    array_push($letters_ru,$arr_alph_ru[$index]);
                }
                if (in_array($lt['letter'],$arr_alph_en)){
                    //если буква английская, транслит не нужен, только сама буква
                    array_push($letters_en,$lt['letter']);
                }
            }
            
            //response
            //определяем текущую букву, которая будет <span> в списке букв
            if (!empty($this_page->page_parameters[1])){
                $index=array_search($this_page->page_parameters[1],$subst_ru);
                if ($index!==false){
                    $letter=$arr_alph_ru[$index];
                    Response::SetString('letter_current',$letter);
                }
                elseif(in_array($this_page->page_parameters[1],$arr_alph_en)){
                    $letter=$this_page->page_parameters[1];
                    Response::SetString('letter_current',$letter);
                }
            }
            Response::SetArray('letters_ru',$letters_ru);
            Response::SetArray('l_subst_ru',$l_subst_ru);
            Response::SetArray('letters_en',$letters_en);
            Response::SetString('h1', empty($this_page->page_seo_h1)?'Словарь': $this_page->page_seo_h1);
        } else $this_page->http_code = 404;
        break;
        
    //###########################################################################
    // выводим слово и значение
    //###########################################################################
    case ((empty($this_page->page_parameters[2]))&&(!empty($this_page->page_parameters[1]))&&(Validate::isDigit($this_page->page_parameters[1]))): 
            $id = $this_page->page_parameters[1];
            $module_template = 'item.html';
            //определяем словарь (рус/англ), $letter - полученная буква
            $index=array_search($action,$subst_ru);
            if ($index!==false) $letter=$arr_alph_ru[$index];
            else $letter=$action;
            //читаем значение слова по id и первой букве
            $item = $db->fetch("SELECT * FROM ".$sys_tables['dictionary']." WHERE (".$sys_tables['dictionary'].".id = ".$id.")AND( LEFT(word,1)='".$letter."')"); //добавить условие на букву LEFT ( ... 1)
            if (empty($item)) $this_page->http_code = 404;
            else
            {
                //добавляем breadcrumbs и title
                $this_page->addBreadcrumbs($letter,$action);
                $this_page->addBreadcrumbs($item['word'],$action);
                $new_meta = array('title' =>'Что такое "'.$item['word'].'" - Справочник', 'keywords' =>$item['word']);
                $this_page->manageMetadata($new_meta, true);
                //response
                $h1 = empty($this_page->page_seo_h1) ? $item['word'] : $this_page->page_seo_h1;
                Response::SetString('h1', $h1);
                
                Response::SetArray('item',$item);
            }
        break;
    //###########################################################################
    // выводим список слов и значений по выбранной букве
    //###########################################################################     
    case ((empty($this_page->page_parameters[1]))&&((in_array($action,$subst_ru))||(in_array($action,$arr_alph_en)))): 
            $module_template = 'list.html';
            $page = Request::GetInteger('page',METHOD_GET);
            //редирект с несуществующих пейджей
             if(empty($page)){
                if(isset($page)) Host::Redirect('/'.$this_page->requested_path.'/?page=1');
                $page = 1;
            }
            elseif($page<1) Host::Redirect('/'.$this_page->requested_path.'/?page=1');
            else Response::SetBoolean('noindex',true); //meta-тег robots = noindex
            
            //ищем букву в русском алфавите. Если нашли - получаем ее номер, если не нашли - FALSE
            $index = array_search($action,$subst_ru);
            //читаем список по выбранной букве из базы
            if ($index!==false) {
                $letter = $arr_alph_ru[$index];
                $letter_url = $subst_ru[$index];
            } else $letter = $letter_url = $action;
            // создаем пагинатор для списка
            $where = " LEFT(".$sys_tables['dictionary'].".word,1) = '".$letter."'";
            $paginator = new Paginator($sys_tables['dictionary'], $strings_per_page, $where);
            if($paginator->pages_count>0 && $paginator->pages_count<$page){
                Host::Redirect('/'.$this_page->requested_path.'/?page='.$paginator->pages_count);
                exit(0);
            }
            //формирование url для пагинатора
            $paginator->link_prefix = '/'.$this_page->requested_path.'/?page=';
            if($paginator->pages_count>1){
                Response::SetArray('paginator', $paginator->Get($page));
            }
            //выбираем страницы для отображения
            $list = $db->fetchall("SELECT id,word,meaning FROM ".$sys_tables['dictionary']." 
                                    WHERE LEFT(".$sys_tables['dictionary'].".word,1) = '".$letter."' 
                                    ORDER BY word
                                    LIMIT ".$paginator->getFromString($page).",".$strings_per_page);
            
            //response
            Response::SetArray('list',$list);
            Response::SetString('letter',$action);
            
            $h1 = empty($this_page->page_seo_h1) ? 'Словарь: '.$letter : $this_page->page_seo_h1;
            Response::SetString('h1', $h1);
            //title
            $this_page->manageMetadata(array('title' =>'Словарь: '.$letter.' - Справочник', 'keywords' =>$letter), true);
            //добавляем breadcumbs
            $this_page->addBreadcrumbs($letter,$letter_url);
            
        break;
   default:
        $this_page->http_code = 404;
        break;
}
?>