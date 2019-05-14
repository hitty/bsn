jQuery(document).ready(function(){
    var _wrap = jQuery('.social-attach-selector');
    var _form = jQuery('#item-edit-form');
    jQuery('.selector-title.enabled.active',_form).on('click',function(){
        jQuery(this).removeClass('active').siblings('span').addClass('active').siblings('input').val('0');
        jQuery('button',_form).click();
    })
});           
