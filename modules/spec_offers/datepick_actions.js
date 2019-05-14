jQuery(function(){    //datepicker init
    var _form = jQuery(".form_default");
    jQuery( "#date_start, #date_end",_form ).datepicker({
        defaultDate: "0",
        hideIfNoPrevNext: true,
        numberOfMonths: 2,
        dateFormat: 'dd.mm.yy'
    });    
    // show button when both date picked
    var show = 'yes';
    jQuery('#date_start, #date_end',_form).each(function(index){
        if(jQuery(this).attr('value')=='') show = 'none';
    });
    var button = jQuery('#submitButton',_form);
    
    if(show == 'yes')  {button.fadeIn(200);}
    else {button.hide(2);}
    jQuery('#date_start, #date_end',_form).change(function(){
        show = 'yes'; 
        jQuery('#date_start, #date_end',_form).each(function(index){
            if(jQuery(this).val()=='') show = 'none';
        });
        if(show == 'yes')  {button.fadeIn(200);}
        else {button.hide(2);}
    });
});
