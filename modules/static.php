<?php
Response::SetBoolean( 'payed_format', true );
if(in_array($this_page->real_path,array('rsti','arenda-ofisa-ot-sobstvennika','kommercheskie-pomescheniya-life-primorskiy','dom_na_frunzenskoy'))) {
    $GLOBALS['css_set'][] = '/css/style.sale.css';
    $GLOBALS['js_set'][] = '/modules/estate/yandex.map.js';
}

if(empty($this_page->page_parameters[0])){
    
    if(!in_array('/modules/tgb/list.popup.js',$GLOBALS['js_set'])) $GLOBALS['js_set'][] = '/modules/tgb/list.popup.js';
    if(!in_array('/modules/tgb/list.popup.css',$GLOBALS['css_set'])) $GLOBALS['css_set'][] = '/modules/tgb/list.popup.css';
    
    $GLOBALS['css_set'][] = '/css/static.css';
    $module_template = 'templates/static/static.html';
    //обработка параметра, который отвечает за наличие php вставок в контент
    $tpl = new Template(false, false,0, $this_page->page_content);
    $static_content = $tpl->Processing();
    Response::SetString('content',$static_content);

    
} else $this_page->http_code=404;
Response::SetBoolean('show_overlay', true);
?>
