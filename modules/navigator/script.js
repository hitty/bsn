_window_scroll_top = 0;
jQuery(document).ready(function(){
    jQuery('.item').each(function(){ jQuery(this).popupWindow() });

    var loc = history.location || document.location;
    var _val = getGPval(loc);
    if(_val != '') jQuery(".item[data-location = '" + _val + "']").click();
    jQuery(window).bind( "popstate", function( e ) {
        var loc = history.location || document.location;
        var _val = getGPval(loc);
        //if(_val != '') jQuery(".item[data-location = '" + _val + "']").click();
    });    

    jQuery(window).scroll(function(){
        if(!jQuery('body').hasClass('fixed')) _window_scroll_top = jQuery(this).scrollTop();
    });    
        
})