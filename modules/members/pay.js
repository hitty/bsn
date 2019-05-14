jQuery(document).ready(function(){
    if(jQuery('.raising-period').length > 0){
        jQuery('.raising-period').on('change', function(){
            
            if(jQuery('#raising-period').val() == 1) _cost = 30;
            else _cost = 120;
            
            jQuery('.raising.active').children('.cost-for-one').children('i:not(.rur)').html(_cost);
            jQuery('.calculate b').html(_cost+'<i class="rur">a</i>');
            
            if(jQuery('.objects-wrap').length > 0)
                if(jQuery('#raising-period').val() == 1) _cost = parseInt(jQuery('.objects-wrap h3 i').html())*30;
                else _cost = parseInt(jQuery('.objects-wrap h3 i').html())*120;
            else jQuery('.total-summ i').children('i:not(.rur)').html(_cost);
            jQuery('.total-summ i').children('i:not(.rur)').html(_cost);
            jQuery('input[name=agency_object_long]').val(jQuery('#raising-period').val());
            jQuery('input[name=summ]').val(_cost);
        });
    }
    
    if(jQuery('.payed-rent').length > 0){
        jQuery('.payed-rent').on('change', function(){
            
            if(jQuery('#payed-rent').val() == 1) _one_cost = 10;
            else _one_cost = parseInt(jQuery('.payed-rent .list-data').find('.selected').attr('data-cost'));
            
            jQuery('.raising.active').children('.cost-for-one').children('i:not(.rur)').html(_one_cost);
            jQuery('.calculate b').html(_one_cost+'<i class="rur">a</i>');
            
            if(jQuery('.objects-wrap').length > 0) _cost = parseInt(jQuery('.objects-wrap h3 i').html())*_one_cost;
            else jQuery('.total-summ i').children('i:not(.rur)').html(_cost);
            jQuery('.total-summ i').children('i:not(.rur)').html(_cost);
            jQuery('input[name=agency_object_long]').val(jQuery('#payed-rent').val());
            jQuery('input[name=summ]').val(_cost);
        });
    }
    
    jQuery('.object .actions .delete, .object .actions .restore').on('click',function(){
        var _this_object = jQuery(this).parents('.object');
        var _this_id = _this_object.attr('data-id');
        var _pay_form = jQuery('#pay-form');
        //убираем из списка в форме оплаты
        if(jQuery(this).hasClass('restore')){
            _pay_form.children('input[name="id_object"]').val(_pay_form.children('input[name="id_object"]').val().replace('"'+_this_object.attr('data-type')+'":"','"'+_this_object.attr('data-type')+'":"'+_this_id+','));
            //отмечаем объект как убранный из списка
            _this_object.removeClass('removed');
            jQuery(this).addClass('delete').removeClass('restore').children('i').html("Удалить");
        }
        //восстанавливаем объект в списке
        else{
            _pay_form.children('input[name="id_object"]').val(_pay_form.children('input[name="id_object"]').val().replace(_this_id,''));
            //отмечаем объект как убранный из списка
            _this_object.addClass('removed');
            jQuery(this).addClass('restore').removeClass('delete').children('i').html("Вернуть");
        }
        
        //корректируем общее количество объектов на оплату
        jQuery('.objects-wrap').children('h3').children('i').html(jQuery('.objects-list').children('.object:not(.removed)').length);
        
        //пересчитываем сумму:
        jQuery.ajax({
            type: "POST", async: true,
            dataType: 'json', url: '/members/refresh_sum/',
            data: {ajax: true, id_object: _pay_form.children('input[name="id_object"]').val(), 
                               status: _pay_form.children('input[name="status"]').val(), 
                               agency_object_long: _pay_form.children('input[name="agency_object_long"]').val()},
            success: function(msg){
                if(msg.ok){
                    _pay_form.children('input[name="summ"]').val(msg.summ);
                    if(jQuery('.pay-total').children('.calculate.free').length != 0) jQuery('.pay-total').children('.calculate.free').children('i').html(msg.free + ' ' + makeSuffix(msg.free,Array('объект','объекта','объектов')));
                    if(jQuery('.pay-total').children('.calculate.payed').length != 0) jQuery('.pay-total').children('.calculate.payed').children('i').html(msg.payed + ' ' + makeSuffix(msg.payed,Array('объект','объекта','объектов')));
                    jQuery('.total-summ').children('i').children('i:not(.rur)').html(msg.summ);
                }
            }
        });
    });
    
    jQuery('.pay_method').click(function() {
        jQuery(this).children('strong').toggleClass('no_border');
        jQuery(this).next('div').slideToggle(100).css({display:'block'});
    });
    
    if(jQuery('.promocode-wrap').length > 0){
        var _button = jQuery('.promocode-activate');
        var _input = jQuery('#promocode');
        var _wrap = jQuery('.promocode-wrap');
        _button.on('click', function(){
            _input.removeClass('red-border') ;
            jQuery('.error', _wrap).remove() ;
            var _val = _input.val();
            if( _val.length < 3 ) {
                _input.addClass('red-border') ;
                return false;
            }
            jQuery.ajax({
                type: "POST", async: true,
                dataType: 'json', url: '/members/promocode/check/',
                data: {ajax: true, value: _val, summ: jQuery('.pay-input').val()},
                success: function(msg){
                    if(typeof msg.error == 'boolean') {
                        _input.addClass('red-border').removeClass('green-border');
                    }
                    else {
                        _input.removeClass('red-border').addClass('green-border').attr('disabled', 'disabled');
                        _button.remove();
                    }
                    _wrap.append(msg.html)
                }
                
            })            
        })
    }
    
    jQuery('.agency-object-long input').on('click', function(){
        var _this = jQuery(this)    ;
        jQuery('#pay-form input[name=agency_object_long]').val(_this.is(':checked'));
    });
    
    if(jQuery('.list-selector.active').length > 0) jQuery('#pay-form').find('input[name="agency_object_long"]').val(jQuery('.list-selector.active').find('input').val());
    else jQuery('#pay-form input[name=agency_object_long]').val(jQuery('.agency-object-long input').is(':checked'));
});