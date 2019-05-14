jQuery(document).ready(function(){
    //переход на нужную ипотеку со страницы выбора
    jQuery('.radioloop input').on('click',function(){
        jQuery(this).attr('checked',true).siblings().removeAttr('checked');
        var _params = {
            estate_type:jQuery(this).attr('data-estate-type')
        }
        getPendingContent('.line.tall','/mortgage/get-banks-list/',_params,false,false,false,false);
        setTimeout("jQuery('#estate_price').trigger('keyup');jQuery('.two-columned-fields').addClass('active');",400);
        jQuery('.mortgage-application').attr('data-estate-type',_params.estate_type);
        jQuery("input[type='checkbox']").off("click");
        jQuery(document).on('click',"input[type='checkbox']",function(){
            var _this = jQuery(this); 
            if(_this.is(':checked') == true) _value = _this.data('true-value');
            else _value = _this.data('false-value');
            jQuery('input#'+_this.attr('rel')).val(_value);
        });
        
        jQuery("input[type='checkbox']").off('change');
        jQuery(document).on('change',"input[type='checkbox']",function(){
            if(jQuery(this).is(":checked")) jQuery(this).parent().addClass("on");
            else jQuery(this).parent().removeClass("on");
        });
    });
    jQuery('.radioloop input:checked').trigger('click');
    jQuery('.mortgage-application').addClass('disabled');
});