var _search_flag = false;
jQuery(document).ready(function(){
    jQuery('.expand-button').on('click', function(){
        jQuery(this).hide(0).siblings('.expand').fadeIn(300);
    });
    if(jQuery('.corner-consultant').length==0){
        jQuery('.lz_cbl').addClass('align-right');
    }

    jQuery('.tab-offers').on('click', function(){
        jQuery('.dashed-link-blue[data-tab-ref=".objects"]').click();
    })
    jQuery('.read-next').on('click', function (event){
        jQuery('.notes-box div').css({'height':'auto', 'max-height':'auto'}).addClass('expanded');
        if(jQuery('.titles-block .titles-box .description').length>0){
            jQuery('.titles-block .titles-box .description').removeClass('shortened').css({'height':'auto', 'max-height':'auto'});
            jQuery('#application-button').offset({ top: jQuery('.specialization').offset().top-jQuery(this).height()+jQuery('.specialization').height()});
        }
        jQuery(this).fadeOut();
        return false
    });     
    jQuery(".filter span[data-tab-ref='.objects']").on('click', function(){
        if(jQuery("#fast-search-form").length > 0 && !_search_flag) {
            _search_flag = true;
            _ajax_search = true;
            search_result();
        }
    })
    
});   





     