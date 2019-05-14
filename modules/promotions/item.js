jQuery(document).ready(function(){
    if( jQuery('.promotions-estate-list').length > 0 ) {
        jQuery('.promotions-estate-list').ajaxfilter({
            url_element             : '#promotions-objects .filter span',
            scroll_to_element       : '#promotions-objects',
            limit_on_page_element   : '#count_selector',
            page_element            : '.paginator',
            sorting_element         : '#sort_selector'
        });
    }
    jQuery('.promotions .content').addClass('item-styled');
    if(jQuery('.promotions-list').length == 1){
        jQuery('.promotions').addClass('tall-1');
    }
    else if(jQuery('.promotions-list').length == 2){
        jQuery('.promotions').addClass('tall-2');
    }
    jQuery('.slogan').addClass('margined');
})
