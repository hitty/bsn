_window_scroll_top = 0;
jQuery(document).ready(function(){
    jQuery('.item').each(function(){ jQuery(this).popupWindow({'konkurs_url':'konkurs_doverie_potrebiteley_2018'}) });

    var loc = history.location || document.location;
    var _val = getGPval(loc);
    if(_val != '') jQuery(".item[data-location = '" + _val + "']").click();
    
     
})

   