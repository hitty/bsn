jQuery(document).ready(function(){
    /* tabs (with pending content upload) */
    jQuery(".last-objects-title").on('click', '.ajax-last-offers-container span',function(){
        var el = jQuery(this);
        if(el.hasClass("active")) return false;
        var cont = el.parent();
        jQuery("span", cont).removeClass("active");
        if(getPendingContent(cont.attr("data-content-container"),cont.attr("data-url")+el.attr("data-param")+'/'))  el.addClass("active");
        el.siblings('a').removeClass('active').siblings('a[data-type='+el.attr("data-param")+']').addClass('active');
        cont.parents('h2').siblings('a').attr('href',cont.find('a.active').attr('href'));
    })
    
    jQuery(".ajax-object-types-container span").on('click', function(){
        var el = jQuery(this);
        if(el.hasClass("green")) return false;
        var cont = el.parent();
        
        var _param =  el.attr('data-param');
        var target_cont =  jQuery(cont.attr('data-content-container'));
        target_cont.children('li[rel!='+_param+']').fadeOut(0);
        target_cont.children('li[rel='+_param+']').fadeIn(200).css({'display':'inline-block'});
        
        el.addClass('green').removeClass("white").siblings("span").addClass("white").removeClass("green");

        el.siblings('a').attr('href', el.siblings('a').attr('href').replace(_param=='sell'?'rent':'sell',_param)).find('span').text(_param=='sell'?'продажу':'аренду');
    })
    
});