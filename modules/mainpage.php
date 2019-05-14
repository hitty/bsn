<?php
require_once('includes/class.estate.statistics.php');

$GLOBALS['js_set'][] = '/js/mainpage.js';
$GLOBALS['css_set'][] = '/css/mainpage.css';

// подключение поисковой формы 
require_once("includes/form.estate.php");
$GLOBALS['js_set'][] = '/js/form.validate.js';

$GLOBALS['js_set'][] = '/js/jquery.estate.search.js';
$GLOBALS['css_set'][] = '/css/estate_search.css';

$GLOBALS['js_set'][] =  '/js/jquery.typewatch.js';
$GLOBALS['css_set'][] = '/css/autocomplete.css';
$GLOBALS['js_set'][] =  '/modules/content/subscribe.js';
$GLOBALS['css_set'][] = '/css/estate_catalog.css';

//для ТГБ со всплывашками
$GLOBALS['css_set'][] = '/modules/applications/style.css';

$module_template = 'mainpage.html';

Response::SetBoolean('search_form', true);
Response::SetBoolean('estate_object', 'build');
//подсчет кол=ва порулярных объектов
$popular_list = $memcache->get('bsn::estate::popular_list');
if($popular_list === FALSE) {
    $popular_list = EstateStat::GetCountPopular(false);
    $memcache->set('bsn::estate::popular_list', $popular_list, FALSE, Config::$values['blocks_cache_time']['estate_popular_list']);
}
Response::SetArray('popular_list', $popular_list);

$GLOBALS['css_set'][] = '/css/p-carousel.css';
Response::SetBoolean('mainpage',true);
?>
