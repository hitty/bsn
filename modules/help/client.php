<?php
require_once('includes/class.content.php');
if( !class_exists( 'Photos') ) if( !class_exists( 'Photos') ) require_once('includes/class.photos.php');

$GLOBALS['css_set'][] = '/modules/help/style.css';
$GLOBALS['css_set'][] = '/modules/search/style.css';

// список не пустых категорий для левого меню
$categories = $db->fetchall("SELECT * FROM ".$sys_tables['help_categories']." 
                                WHERE published=1 
                                AND (SELECT count(id) FROM ".$sys_tables['help_articles']." 
                                WHERE id_category = help_categories.id ) > 0 
                                ORDER BY position");

/* ключами массива $categories становится поле id 
 * в PHP => 5.5 можно сделать так:
 * $categories = array_combine(array_column($categories,'id'),array_values($categories));
*/ 
$new_keys = [];
foreach ($categories as $key => $value) $new_keys[] = $value['id'];
$categories = array_combine($new_keys,array_values($categories));

//список статей для раздела "Популярное"
$popular_articles = $db->fetchall("SELECT
                                ha.title as atitle,
                                ha.chpu_title as achpu_title,
                                hc.chpu_title as cchpu_title
                                FROM ".$sys_tables['help_articles']." as ha 
                                LEFT JOIN ".$sys_tables['help_categories']." as hc
                                ON (ha.id_category = hc.id)
                                WHERE hc.published=1 AND ha.published=1
                                ORDER BY ha.views_count DESC LIMIT 0,5");
               
switch(true){
    case (!empty($this_page->page_parameters[0]) && !empty($this_page->page_parameters[1])):
        //страница статьи
        $module_template = 'article.html';
        
        //предотвращение ссылок с бОльшим количеством параметров в URI
        if(count($this_page->page_parameters)>3) {$this_page->http_code=404; break;}

        //обработка chpu_title
        if(Validate::isDigit($this_page->page_parameters[1])){
            $res = $db->fetch("SELECT chpu_title FROM ".$sys_tables['help_articles']." WHERE published=1 AND id=?", $this_page->page_parameters[1]);
            if(empty($res)){$this_page->http_code=404; break;}
            Host::Redirect("help/".$this_page->page_parameters[0]."/".$res['chpu_title']."/");   
        }  else {
            list($article_id, $chpu) = preg_split("/\_/",$this_page->page_parameters[1],2);
            if(!Validate::isDigit($article_id)){$this_page->http_code=404; break;}
        }
        
        list($category_id,$chpu) = preg_split("/\_/",$this_page->page_parameters[0],2);
        if(!Validate::isDigit($category_id)){$this_page->http_code=404; break;}
        
        //выборка запрошенной статьи    
        $article = $db->fetch("SELECT * FROM ".$sys_tables['help_articles']." 
                                        WHERE published = 1 
                                        AND id=".$article_id." 
                                        AND chpu_title ='".$this_page->page_parameters[1]."'
                                        AND id_category = '".$category_id."'");
                                        
        if (empty($article)) {$this_page->http_code=404; break;}
        
        //инкремент полей полезности/бесполезности статьи с проверкой третьего параметра URI на валидность
        $usefulness = array( 'useful', 'useless' );
        $section = 'help'.$article_id;
        if (!empty($this_page->page_parameters[2]) && !in_array($this_page->page_parameters[2],$usefulness)) {$this_page->http_code=404; break;}
        if (!empty($this_page->page_parameters[2])){
            if(Validate::CanVote($section,2592000,$this_page->page_parameters[2])){ //значение 2592000 = 30 дней не дает голосовать
                $db->query("UPDATE ".$sys_tables['help_articles']." 
                            SET ".$this_page->page_parameters[2]." = ".$this_page->page_parameters[2]." + 1 
                            WHERE id=".$article_id);
                $_COOKIE['bsnvoteinterval'.$section] = $this_page->page_parameters[2];
            }
        }
        
        if (!Validate::CanVote($section)){
            Response::SetString('already_voted',$_COOKIE['bsnvoteinterval'.$section]);
        }
        //инкремент просмотра статьи
        $db->query("UPDATE ".$sys_tables['help_articles']." SET views_count = views_count + 1 WHERE id=".$article_id);
        
        //хлебные крошки
        $tag_title = $categories[$article['id_category']]['title']; 
        $action = $this_page->page_parameters[0];
        $this_page->addBreadcrumbs($tag_title, $action);
        $tag_title = $article['title'];
        $action = $this_page->page_parameters[1]; 
        $this_page->addBreadcrumbs($tag_title, $action);
        
        //метаданные: title,description
        $new_meta = array('title'=>$article['title']." - Помощь по сервисам и услугам BSN.ru",
                          'description'=>'Ответы на все вопросы по сервисам сайта, личному кабинету, услугам портала BSN.ru: '.$article['title']);
        $this_page->manageMetadata($new_meta, true);
        
        //отправка данных в шаблон
        Response::SetArray('article',$article);
        Response::SetInteger('selected_category',$category_id);
        break;
    case (!empty($this_page->page_parameters[0]) && empty($this_page->page_parameters[1])):
        //страница категории
        $module_template = 'category.html';
        
        //предотвращение ссылок с бОльшим количеством параметров в URI
        if(count($this_page->page_parameters)>1) {$this_page->http_code=404; break;}

        //обработка chpu_title
        if(Validate::isDigit($this_page->page_parameters[0])){
            $res = $db->fetch("SELECT chpu_title FROM ".$sys_tables['help_categories']." WHERE published=1 AND id=?", $this_page->page_parameters[0]);
            if(empty($res)){$this_page->http_code=404; break;}
            Host::Redirect("help/".$res['chpu_title'])."/";   
        }  else {
            list ($id,$chpu) = preg_split("/\_/",$this_page->page_parameters[0],2);
            if(!Validate::isDigit($id)){$this_page->http_code=404; break;}
        }
        
        //получение списка статей выбранной категории
        $articles_list = $db->fetchall("SELECT * FROM ".$sys_tables['help_articles']." WHERE published = 1 AND id_category=".$id." ORDER BY position");
        if (empty($articles_list)) {$this_page->http_code=404; break;}
        //получаем фото категории
        $categories[$id]['photo'] = $db->fetchall("SELECT name,MID(name,1,2) as subfolder FROM ".$sys_tables['help_categories_photos']." WHERE id=".$categories[$id]['id_main_photo']);
        //хлебные крошки
        $tag_title = $categories[$id]['title']; 
        $action = $this_page->page_parameters[0];
        $this_page->addBreadcrumbs($tag_title, $action);
        
        Response::SetArray('articles',$articles_list);
        Response::SetInteger('selected_category',$id);
        break;
    default:
        //главная страница раздела со списком категорий и статей
        $module_template = 'categories.list.html';
        $GLOBALS['css_set'][] = '/modules/search/style.css';
         
        //выбираем все статьи с категориями и фото
        $articles_list = $db->fetchall("SELECT *,".$sys_tables['help_categories'].".title as ctitle,
                                                 ".$sys_tables['help_categories'].".id as cid,
                                                 ".$sys_tables['help_articles'].".id as aid,
                                                 ".$sys_tables['help_articles'].".chpu_title as achpu_title,
                                                 ".$sys_tables['help_articles'].".title as atitle,
                                                 SUBSTRING(".$sys_tables['help_categories_photos'].".name,1,2) as subfolder,
                                                 ".$sys_tables['help_categories_photos'].".name as photo
                                                 FROM ".$sys_tables['help_articles']." 
                                                 LEFT JOIN ".$sys_tables['help_categories']."
                                                 ON (".$sys_tables['help_categories'].".id=".$sys_tables['help_articles'].".id_category)
                                                 LEFT JOIN ".$sys_tables['help_categories_photos']."
                                                 ON (".$sys_tables['help_categories_photos'].".`id` = ".$sys_tables['help_categories'].".`id_main_photo`)
                                                 WHERE ".$sys_tables['help_categories'].".published=1 
                                                 AND ".$sys_tables['help_articles'].".published=1 
                                                 ORDER BY ".$sys_tables['help_categories'].".position,".$sys_tables['help_articles'].".position");
        //добавляем статьи в массив категорий в ключ articles
        foreach ($articles_list as $key => $value){
            $categories[$value['cid']]['articles'][] = $value;
            $categories[$value['cid']]['alias'] = preg_replace('/[0-9]+_/','',$categories[$value['cid']]['chpu_title']);
        }
            
        Response::SetArray('categories_blocks',$categories);
        break;
}

//исключения и параметры для поисковой формы
Response::SetString('not_show_params', 'yes');
Response::SetString('selected_type','help');

Response::SetArray('popular_menu',$popular_articles);
Response::SetString('host', Host::$host);
Response::SetArray('categories_menu',$categories);
?>
