jQuery(document).ready(function(){
    jQuery("#f_user_id").on("change", function(event){
        if (jQuery('#date_start').val().length!=0 && jQuery('#date_start').val().length>0 && jQuery('#date_end').val().length>0)
            getData();
    });
    jQuery("#f_estate_type").on("change", function(event){
        //jQuery(this).children().first().attr('disabled','true');
        if (jQuery('#date_start').val().length!=0 && jQuery('#date_start').val().length>0 && jQuery('#date_end').val().length>0){
            getData();
        }
    });
    jQuery('.form_default').children('.button').on('mouseup',function(){
        
    });
    jQuery('#date_start').on('change',function(){
        if(jQuery('#date_end').val().length > 0) getData();
    });
    jQuery('#date_end').on('change',function(){
        if(jQuery('#date_start').val().length > 0) getData();
    });
});