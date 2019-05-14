jQuery(document).ready(function(){
    jQuery('.list_table').click(function(e){
        var _target = jQuery(e.target);
        switch(true){
            case _target.hasClass('ico_del') :
                if(!confirm('Вы уверены, что нужно удалить этот объект?')) return false;
                jQuery.ajax({
                    type: "POST", async: true,
                    dataType: 'json', url: _target.parent().attr('href'),
                    data: {ajax: true},
                    success: function(msg){
                        if(typeof(msg)=='object') {
                            if(msg.ok) {
                                if(msg.ids.length){
                                    var _obj = null;
                                    for(var i=0;i<msg.ids.length;i++){
                                        _obj = jQuery('#item_'+msg.ids[i]);
                                        _obj.fadeOut(500,function(){_obj.remove();});
                                    }
                                } else alert('Ни один элемент не удален.');
                            } else alert('Ошибка: '+msg.error);
                        } else alert('Ошибка!');
                    },
                    error: function(){
                        alert('Server connection error!');
                    },
                    complete: function(){
                    }
                });
                return false;
        }
        return true;
    });
    checkForm();
    jQuery('#redirect_to_offers').click(function(e){
        var _form = jQuery('#offer_form');
        _form.attr('action',jQuery(this).data('href')+'&redirect=add_offers');
        _form.submit();
    })
    
    //fileuploader init
    if(jQuery('#file_upload').length>0){
        jQuery('#file_upload').uploadifive({
                'multi':false,
                'queueSizeLimit': 1,
                onChangeCount: function(){
                    checkForm();
                }
            }
        );
    }

    if(jQuery('#discount').size()>0){
        //discount count
        jQuery('#discount').parent('span').append('<div id="cost_w_discount_wrap">Стоимость со скидкой: <b></b> руб.')
        if(jQuery('#discount_in_rubles').val().replace(/\s/g,'') == 0) jQuery('#discount_in_rubles').addClass('disabled');
        else jQuery('#discount').addClass('disabled');
        
        jQuery('#discount_in_rubles').parent('span').click(function(){ 
            jQuery('#discount_in_rubles').removeClass('disabled');
            jQuery('#discount').val(0).addClass('disabled');
        })
        jQuery('#discount').parent('span').click(function(){   
             jQuery('#discount_in_rubles').val(0).addClass('disabled');
             jQuery('#discount').removeClass('disabled');
        })
        
        jQuery('#cost, #discount, #discount_in_rubles').on('keyup',function(){
            var _cost_w_discount = jQuery('#cost_w_discount');
            var _cost = jQuery('#cost');
            var _discount = jQuery('#discount');
            var _discount_in_rubles = jQuery('#discount_in_rubles');
            var _discount_wrap = _discount.parent('span');
            var _discount_in_rubles_wrap = _discount_in_rubles.parent('span');
            var _value = parseInt(_cost.val().replace(/\s/g,''));
            if(_value>0){
                if(_discount_in_rubles.val().replace(/\s/g,'') > 0  && _discount.val() == 0)  _value = _value - parseInt(_discount_in_rubles.val().replace(/\s/g,''));
                else if(_discount_in_rubles.val().replace(/\s/g,'') == 0  && _discount.val() > 0)  {
                    _value =  parseInt( _value * (1 - parseInt(_discount.val())/100) );
                }
                _cost_w_discount.val(_value);
                jQuery('#cost_w_discount_wrap b').text(_value);
            } else {
                _discount.val(0);
                _discount_in_rubles.val(0);
                _cost_w_discount.val(0);
                jQuery('#cost_w_discount_wrap b').text('-');
            }
            return false;
        })
        jQuery('#discount').keyup();
    }    
});


